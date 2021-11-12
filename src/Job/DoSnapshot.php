<?php
namespace Osii\Job;

use DateTime;
use Laminas\Http\Client;
use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;
use Osii\Entity as OsiiEntity;

class DoSnapshot extends AbstractJob
{
    protected $importEntity;

    protected $allProperties = [];
    protected $allClasses = [];
    protected $allVocabularies = [];

    protected $usedDataTypes = [];
    protected $usedProperties = [];
    protected $usedClasses = [];

    protected $snapshotDataTypes = [];
    protected $snapshotProperties = [];
    protected $snapshotClasses = [];
    protected $snapshotVocabularies = [];

    /**
     * Sync a dataset with its item set.
     */
    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        $importId = $this->getArg('import_id');

        // Set the import entity.
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $this->importEntity = $entityManager->find(OsiiEntity\OsiiImport::class, $importId);

        // Cache all remote properties, classes, and vocabularies.
        $this->cacheAllProperties();
        $this->cacheAllClasses();
        $this->cacheAllVocabularies();

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

        while (true) {
            $items = $this->getApiOutput($client, $query);
            if (!$items) {
                break; // No more items.
            }
            foreach ($items as $item) {
                // Save snapshots of remote items.
                $itemEntity = $entityManager->find(OsiiEntity\OsiiItem::class, $item['o:id']);
                if (null === $itemEntity) {
                    // This is a new remote item.
                    $itemEntity = new OsiiEntity\OsiiItem;
                    $itemEntity->setImport($this->importEntity);
                    $itemEntity->setRemoteItemId($item['o:id']);
                    $entityManager->persist($itemEntity);
                } else {
                    // This is an existing remote item.
                    $itemEntity->setModified(new DateTime('now'));
                }
                $itemEntity->setSnapshotItem($item);
                // Cache used data types and properties.
                $values = $this->getValuesFromResource($item);
                foreach ($values as $value) {
                    $this->usedDataTypes[] = $value['type'];
                    $this->usedProperties[] = $value['property_id'];
                }
                // Cache used classes.
                if (isset($item['o:resource_class'])) {
                    $this->usedClasses[] = $item['o:resource_class']['o:id'];
                }
            }
            // Save memory by flushing and clearing the entity manager at the
            // end of every iteration. We must re-load the import entity to
            // avoid a "A new entity was found" error.
            $entityManager->flush();
            $entityManager->clear();
            $this->importEntity = $entityManager->find(OsiiEntity\OsiiImport::class, $importId);
            // Increment the page.
            $query['page']++;
        }

        $this->usedDataTypes = array_count_values($this->usedDataTypes);
        $this->usedProperties = array_count_values($this->usedProperties);
        $this->usedClasses = array_count_values($this->usedClasses);
        arsort($this->usedDataTypes, SORT_NUMERIC);
        arsort($this->usedProperties, SORT_NUMERIC);
        arsort($this->usedClasses, SORT_NUMERIC);

        // Cache the snapshot data types.
        foreach ($this->usedDataTypes as $dataTypeId => $count) {
            $this->snapshotDataTypes[$dataTypeId] = [
                'label' => null, // Placeholder until data_types resource is available
                'count' => $count,
            ];
        }
        // Cache the snapshot properties (and vocabularies).
        foreach ($this->usedProperties as $propertyId => $count) {
            $property = $this->allProperties[$propertyId];
            $vocabulary = $this->allVocabularies[$property['o:vocabulary']['o:id']];
            $this->snapshotProperties[$vocabulary['o:namespace_uri']][$propertyId] = [
                'label' => $property['o:label'],
                'local_name' => $property['o:local_name'],
                'count' => $count,
            ];
            $this->snapshotVocabularies[$vocabulary['o:namespace_uri']] = [
                'label' => $vocabulary['o:label'],
            ];
        }
        // Cache the snapshot classes (and vocabularies).
        foreach ($this->usedClasses as $classId => $count) {
            $class = $this->allClasses[$classId];
            $vocabulary = $this->allVocabularies[$class['o:vocabulary']['o:id']];
            $this->snapshotClasses[$vocabulary['o:namespace_uri']][$classId] = [
                'label' => $class['o:label'],
                'local_name' => $class['o:local_name'],
                'count' => $count,
            ];
            $this->snapshotVocabularies[$vocabulary['o:namespace_uri']] = [
                'label' => $vocabulary['o:label'],
            ];
        }

        $this->importEntity->setSnapshotDataTypes($this->snapshotDataTypes);
        $this->importEntity->setSnapshotProperties($this->snapshotProperties);
        $this->importEntity->setSnapshotClasses($this->snapshotClasses);
        $this->importEntity->setSnapshotVocabularies($this->snapshotVocabularies);

        $entityManager->flush();
    }

    /**
     * Cache all remote vocabularies.
     */
    protected function cacheAllVocabularies()
    {
        $endpoint = sprintf('%s/vocabularies', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        while (true) {
            $vocabularies = $this->getApiOutput($client, $query);
            if (!$vocabularies) {
                break; // No more vocabularies.
            }
            foreach ($vocabularies as $vocabulary) {
                $this->allVocabularies[$vocabulary['o:id']] = $vocabulary;
            }
            $query['page']++;
        }
    }

    /**
     * Cache all remote properties.
     */
    protected function cacheAllProperties()
    {
        $endpoint = sprintf('%s/properties', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        while (true) {
            $properties = $this->getApiOutput($client, $query);
            if (!$properties) {
                break; // No more properties.
            }
            foreach ($properties as $property) {
                $this->allProperties[$property['o:id']] = $property;
            }
            $query['page']++;
        }
    }

    /**
     * Cache all remote classes.
     */
    protected function cacheAllClasses()
    {
        $endpoint = sprintf('%s/resource_classes', $this->importEntity->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        while (true) {
            $classes = $this->getApiOutput($client, $query);
            if (!$classes) {
                break; // No more classes.
            }
            foreach ($classes as $class) {
                $this->allClasses[$class['o:id']] = $class;
            }
            $query['page']++;
        }
    }

    /**
     * Get the API client.
     *
     * @param string $endpoint
     * @return Client
     */
    protected function getApiClient($endpoint)
    {
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($endpoint);
        return $client;
    }

    /**
     * Get API output.
     *
     * @param Client $client
     * @param array $query
     * @return array
     */
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

    /**
     * Get values from resource API output (JSON-LD).
     *
     * @param array $resource
     * @return array
     */
    protected function getValuesFromResource($resource)
    {
        $resourceValues = [];
        foreach ($resource as $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                if (!is_array($value)) {
                    continue;
                }
                if (isset($value['type']) && isset($value['property_id'])) {
                    $resourceValues[] = $value;
                }
            }
        }
        return $resourceValues;
    }
}
