<?php
namespace Osii\Job;

use Laminas\Http\Client;
use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;
use Osii\Entity as OsiiEntity;

class DoSnapshot extends AbstractJob
{
    protected $importRepresentation;
    protected $importEntity;

    protected $allVocabularies = [];
    protected $allProperties = [];
    protected $allClasses = [];

    protected $usedDataTypes = [];
    protected $usedProperties = [];
    protected $usedClasses = [];

    /**
     * Sync a dataset with its item set.
     */
    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        $importId = $this->getArg('import_id');

        // Set the import representation.
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->importRepresentation = $api->read('osii_imports', $importId)->getContent();
        if (!$this->importRepresentation->canDoSnapshot()) {
            throw new Exception\RuntimeException('Cannot take a snapshot');
        }

        // Set the import entity.
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $this->importEntity = $em->find(OsiiEntity\OsiiImport::class, $importId);

        // Cache all remote vocabularies, properties, and classes.
        $this->cacheAllVocabularies();
        $this->cacheAllProperties();
        $this->cacheAllClasses();

        // Iterate remote items. Cache used data types, properties, and classes.
        $endpoint = sprintf('%s/items', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        parse_str($this->importEntity->getRemoteQuery(), $query);
        $query['key_identity'] = $this->importEntity->getKeyIdentity();
        $query['key_credential'] = $this->importEntity->getKeyCredential();
        $query['sort_by'] = 'id';
        $query['sort_order'] = 'asc';
        $query['per_page'] = 50;
        $query['page'] = 1;

        do {
            $items = $this->getApiOutput($client, $query);
            $values = $this->getValuesFromResourcesOutput($items);
            foreach ($values as $value) {
                $this->usedDataTypes[] = $value['type'];
                $this->usedProperties[] = $value['property_id'];
            }
            $classes = $this->getClassesFromResourcesOutput($items);
            foreach ($classes as $class) {
                $this->usedClasses[] = $class['o:id'];
            }
            $query['page']++;
        } while ($items);

        $this->usedDataTypes = array_count_values($this->usedDataTypes);
        $this->usedProperties = array_count_values($this->usedProperties);
        $this->usedClasses = array_count_values($this->usedClasses);

        arsort($this->usedDataTypes, SORT_NUMERIC);
        arsort($this->usedProperties, SORT_NUMERIC);
        arsort($this->usedClasses, SORT_NUMERIC);

        // print_r($this->usedDataTypes);
        // print_r($this->usedProperties);
        // print_r($this->usedClasses);
    }

    protected function cacheAllVocabularies()
    {
        $endpoint = sprintf('%s/vocabularies', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        do {
            $vocabularies = $this->getApiOutput($client, $query);
            foreach ($vocabularies as $vocabulary) {
                $this->allVocabularies[$vocabulary['o:id']] = $vocabulary;
            }
            $query['page']++;
        } while ($vocabularies);
    }

    protected function cacheAllProperties()
    {
        $endpoint = sprintf('%s/properties', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        do {
            $properties = $this->getApiOutput($client, $query);
            foreach ($properties as $property) {
                $this->allProperties[$property['o:vocabulary']['o:id']][$property['o:id']] = $property;
            }
            $query['page']++;
        } while ($properties);
    }

    protected function cacheAllClasses()
    {
        $endpoint = sprintf('%s/resource_classes', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        do {
            $classes = $this->getApiOutput($client, $query);
            foreach ($classes as $class) {
                $this->allClasses[$class['o:vocabulary']['o:id']][$class['o:id']] = $class;
            }
            $query['page']++;
        } while ($classes);
    }

    protected function getApiClient($endpoint)
    {
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($endpoint);
        return $client;
    }

    protected function getApiOutput(Client $client, array $query)
    {
        $client->setParameterGet($query);
        $response = $client->send();
        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException('Cannot resolve API endpoint');
        }
        if (!$response->getHeaders()->get('omeka-s-version')) {
            throw new Exception\RuntimeException('Not an Omeka S endpoint');
        }
        $output = json_decode($response->getBody(), true);
        return $output;
    }

    protected function getValuesFromResourcesOutput($resourcesOutput)
    {
        $values = [];
        foreach ($resourcesOutput as $resourceOutput) {
            foreach ($resourceOutput as $valuesOutput) {
                if (!is_array($valuesOutput)) {
                    continue;
                }
                foreach ($valuesOutput as $valueOutput) {
                    if (!is_array($valueOutput)) {
                        continue;
                    }
                    if (isset($valueOutput['type']) && isset($valueOutput['property_id'])) {
                        $values[] = $valueOutput;
                    }
                }
            }
        }
        return $values;
    }

    protected function getClassesFromResourcesOutput($resourcesOutput)
    {
        $classes = [];
        foreach ($resourcesOutput as $resourceOutput) {
            foreach ($resourceOutput as $key => $classOutput) {
                if (!is_array($classOutput)) {
                    continue;
                }
                if ('o:resource_class' === $key) {
                    $classes[] = $classOutput;
                }
            }
        }
        return $classes;
    }
}
