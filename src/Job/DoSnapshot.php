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

    protected $snapshotItems = [];
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

        // Set snapshot properties, classes, and vocabularies.
        $this->setSnapshotProperties();
        $this->setSnapshotClasses();
        $this->setSnapshotVocabularies();

        // Iterate remote items.
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
            if ($this->shouldStop()) {
                return;
            }
            $items = $this->getApiOutput($client, $query);
            if (!$items) {
                break; // No more items.
            }
            foreach ($items as $item) {
                // Save snapshots of remote items.
                $osiiItemEntity = $entityManager
                    ->getRepository(OsiiEntity\OsiiItem::class)
                    ->findOneBy([
                        'import' => $this->importEntity,
                        'remoteItemId' => $item['o:id'],
                    ]);
                if (null === $osiiItemEntity) {
                    // This is a new remote item.
                    $osiiItemEntity = new OsiiEntity\OsiiItem;
                    $osiiItemEntity->setImport($this->importEntity);
                    $osiiItemEntity->setRemoteItemId($item['o:id']);
                    $entityManager->persist($osiiItemEntity);
                } else {
                    // This is an existing remote item.
                    $osiiItemEntity->setModified(new DateTime('now'));
                }
                $osiiItemEntity->setSnapshotItem($item);

                // Set metadata about the snapshot.
                $this->snapshotItems[] = $item['o:id'];
                if (isset($item['o:resource_class'])) {
                    $classId = $item['o:resource_class']['o:id'];
                    ++$this->snapshotClasses[$classId]['count'];
                }
                foreach ($this->getValuesFromResource($item) as $value) {
                    $dataTypeId = $value['type'];
                    $propertyId = $value['property_id'];
                    if (!isset($this->snapshotDataTypes[$dataTypeId])) {
                        $this->snapshotDataTypes[$dataTypeId] = [
                            'label' => null, // Placeholder until data_types resource is available
                            'count' => 0,
                        ];
                    }
                    ++$this->snapshotDataTypes[$dataTypeId]['count'];
                    ++$this->snapshotProperties[$propertyId]['count'];
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

        // Remove extraneous properties and classes.
        $snapshotProperties = array_filter($this->snapshotProperties, function ($property) {
            return $property['count'];
        });
        $snapshotClasses = array_filter($this->snapshotClasses, function ($class) {
            return $class['count'];
        });

        // Set the snapshot data to the import entity.
        $this->importEntity->setSnapshotDataTypes($this->snapshotDataTypes);
        $this->importEntity->setSnapshotProperties($snapshotProperties);
        $this->importEntity->setSnapshotClasses($snapshotClasses);
        $this->importEntity->setSnapshotVocabularies($this->snapshotVocabularies);
        $this->importEntity->setSnapshotItems($this->snapshotItems);
        $this->importEntity->setSnapshotCompleted(new DateTime('now'));

        $entityManager->flush();
    }

    /**
     * Set all remote properties.
     */
    protected function setSnapshotProperties()
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
                $this->snapshotProperties[$property['o:id']] = [
                    'vocabulary_id' => $property['o:vocabulary']['o:id'],
                    'local_name' => $property['o:local_name'],
                    'label' => $property['o:label'],
                    'count' => 0,
                ];
            }
            $query['page']++;
        }
    }

    /**
     * Set all remote classes.
     */
    protected function setSnapshotClasses()
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
                $this->snapshotClasses[$class['o:id']] = [
                    'vocabulary_id' => $class['o:vocabulary']['o:id'],
                    'local_name' => $class['o:local_name'],
                    'label' => $class['o:label'],
                    'count' => 0,
                ];
            }
            $query['page']++;
        }
    }

    /**
     * Set all remote vocabularies.
     */
    protected function setSnapshotVocabularies()
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
                $this->snapshotVocabularies[$vocabulary['o:id']] = [
                    'namespace_uri' => $vocabulary['o:namespace_uri'],
                    'label' => $vocabulary['o:label'],
                ];
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
        // Increase connection timeout.
        $client->setOptions(['timeout' => 30]);
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
