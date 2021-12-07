<?php
namespace Osii\Job;

use DateTime;
use Laminas\Http\Client;
use Omeka\Job\Exception;
use Osii\Entity as OsiiEntity;

class DoSnapshot extends AbstractOsiiJob
{
    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        // Set initial snapshot data.
        $snapshotItems = [];
        $snapshotMedia = [];
        $snapshotDataTypes = [];
        $snapshotMediaIngesters = [];
        $rootEndpoint = $this->getImportEntity()->getRootEndpoint();
        $snapshotProperties = $this->getSnapshotProperties($rootEndpoint);
        $snapshotClasses = $this->getSnapshotClasses($rootEndpoint);
        $snapshotVocabularies = $this->getSnapshotVocabularies($rootEndpoint);

        $remoteItemsWithMedia = [];

        // Iterate remote items.
        $endpoint = sprintf('%s/items', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        parse_str($this->getImportEntity()->getRemoteQuery(), $query);
        $query['key_identity'] = $this->getImportEntity()->getKeyIdentity();
        $query['key_credential'] = $this->getImportEntity()->getKeyCredential();
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
                $osiiItemEntity = $this->getEntityManager()
                    ->getRepository(OsiiEntity\OsiiItem::class)
                    ->findOneBy([
                        'import' => $this->getImportEntity(),
                        'remoteItemId' => $item['o:id'],
                    ]);
                if (null === $osiiItemEntity) {
                    // This is a new remote item.
                    $osiiItemEntity = new OsiiEntity\OsiiItem;
                    $osiiItemEntity->setImport($this->getImportEntity());
                    $osiiItemEntity->setRemoteItemId($item['o:id']);
                    $this->getEntityManager()->persist($osiiItemEntity);
                } else {
                    // This is an existing remote item.
                    $osiiItemEntity->setModified(new DateTime('now'));
                }
                $osiiItemEntity->setSnapshotItem($item);

                // Set metadata about the snapshot.
                $snapshotItems[] = $item['o:id'];
                if (!empty($item['o:media'])) {
                    foreach ($item['o:media'] as $position => $media) {
                        $remoteItemsWithMedia[$item['o:id']][$media['o:id']] = $position + 1;
                    }
                }
                if (isset($item['o:resource_class'])) {
                    $classId = $item['o:resource_class']['o:id'];
                    ++$snapshotClasses[$classId]['count'];
                }
                foreach ($this->getValuesFromResource($item) as $value) {
                    $dataTypeId = $value['type'];
                    $propertyId = $value['property_id'];
                    if (!isset($snapshotDataTypes[$dataTypeId])) {
                        $snapshotDataTypes[$dataTypeId] = [
                            'label' => null, // Placeholder until data_types resource is available
                            'count' => 0,
                        ];
                    }
                    ++$snapshotDataTypes[$dataTypeId]['count'];
                    ++$snapshotProperties[$propertyId]['count'];
                }
            }
            // Save memory by flushing and clearing the entity manager at the
            // end of every iteration.
            $this->getEntityManager()->flush();
            $this->getEntityManager()->clear();
            // Increment the page.
            $query['page']++;
        }

        // Iterate remote media.
        $endpoint = sprintf('%s/media', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query = [
            'key_identity' => $this->getImportEntity()->getKeyIdentity(),
            'key_credential' => $this->getImportEntity()->getKeyCredential(),
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 50,
        ];
        foreach ($remoteItemsWithMedia as $remoteItemId => $mediaPositions) {
            $osiiItemEntity = $this->getEntityManager()
                ->getRepository(OsiiEntity\OsiiItem::class)
                ->findOneBy([
                    'import' => $this->getImportEntity(),
                    'remoteItemId' => $remoteItemId,
                ]);
            $query['item_id'] = $remoteItemId;
            $query['page'] = 1;
            while (true) {
                if ($this->shouldStop()) {
                    return;
                }
                $medias = $this->getApiOutput($client, $query);
                if (!$medias) {
                    break; // No more media.
                }
                foreach ($medias as $media) {
                    // Save snapshots of remote media.
                    $osiiMediaEntity = $this->getEntityManager()
                        ->getRepository(OsiiEntity\OsiiMedia::class)
                        ->findOneBy([
                            'osiiItem' => $osiiItemEntity,
                            'remoteMediaId' => $media['o:id'],
                        ]);
                    if (null === $osiiMediaEntity) {
                        // This is a new remote media.
                        $osiiMediaEntity = new OsiiEntity\OsiiMedia;
                        $osiiMediaEntity->setImport($this->getImportEntity());
                        $osiiMediaEntity->setOsiiItem($osiiItemEntity);
                        $osiiMediaEntity->setRemoteMediaId($media['o:id']);
                        $this->getEntityManager()->persist($osiiMediaEntity);
                    } else {
                        // This is an existing remote media.
                        $osiiMediaEntity->setModified(new DateTime('now'));
                    }
                    $osiiMediaEntity->setSnapshotMedia($media);
                    $osiiMediaEntity->setPosition($mediaPositions[$media['o:id']]);
                    // Set metadata about the snapshot.
                    $snapshotMedia[] = $media['o:id'];
                    if (isset($media['o:resource_class'])) {
                        $classId = $media['o:resource_class']['o:id'];
                        ++$snapshotClasses[$classId]['count'];
                    }
                    foreach ($this->getValuesFromResource($media) as $value) {
                        $dataTypeId = $value['type'];
                        $propertyId = $value['property_id'];
                        if (!isset($snapshotDataTypes[$dataTypeId])) {
                            $snapshotDataTypes[$dataTypeId] = [
                                'label' => null, // Placeholder until data_types resource is available
                                'count' => 0,
                            ];
                        }
                        ++$snapshotDataTypes[$dataTypeId]['count'];
                        ++$snapshotProperties[$propertyId]['count'];
                    }
                    if (!isset($snapshotMediaIngesters[$media['o:ingester']])) {
                        $snapshotMediaIngesters[$media['o:ingester']] = [
                            'label' => null, // Placeholder until media_ingesters resource is available, if ever
                            'count' => 0,
                        ];
                    }
                    ++$snapshotMediaIngesters[$media['o:ingester']]['count'];
                }
                $this->getEntityManager()->flush();
                $this->getEntityManager()->clear();
                // Increment the page.
                $query['page']++;
            }
        }

        // Remove extraneous properties and classes.
        $snapshotProperties = array_filter($snapshotProperties, function ($property) {
            return $property['count'];
        });
        $snapshotClasses = array_filter($snapshotClasses, function ($class) {
            return $class['count'];
        });

        // Set the snapshot data to the import entity.
        $this->getImportEntity()->setSnapshotItems($snapshotItems);
        $this->getImportEntity()->setSnapshotMedia($snapshotMedia);
        $this->getImportEntity()->setSnapshotDataTypes($snapshotDataTypes);
        $this->getImportEntity()->setSnapshotMediaIngesters($snapshotMediaIngesters);
        $this->getImportEntity()->setSnapshotProperties($snapshotProperties);
        $this->getImportEntity()->setSnapshotClasses($snapshotClasses);
        $this->getImportEntity()->setSnapshotVocabularies($snapshotVocabularies);
        $this->getImportEntity()->setSnapshotCompleted(new DateTime('now'));

        $this->getEntityManager()->flush();
    }

    /**
     * Set all remote properties.
     *
     * @param string $rootEndpoint
     * @return array
     */
    protected function getSnapshotProperties($rootEndpoint)
    {
        $endpoint = sprintf('%s/properties', $rootEndpoint);
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        $snapshotProperties = [];
        while (true) {
            $properties = $this->getApiOutput($client, $query);
            if (!$properties) {
                break; // No more properties.
            }
            foreach ($properties as $property) {
                $snapshotProperties[$property['o:id']] = [
                    'vocabulary_id' => $property['o:vocabulary']['o:id'],
                    'local_name' => $property['o:local_name'],
                    'label' => $property['o:label'],
                    'count' => 0,
                ];
            }
            $query['page']++;
        }
        return $snapshotProperties;
    }

    /**
     * Set all remote classes.
     *
     * @param string $rootEndpoint
     * @return array
     */
    protected function getSnapshotClasses($rootEndpoint)
    {
        $endpoint = sprintf('%s/resource_classes', $rootEndpoint);
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        $snapshotClasses = [];
        while (true) {
            $classes = $this->getApiOutput($client, $query);
            if (!$classes) {
                break; // No more classes.
            }
            foreach ($classes as $class) {
                $snapshotClasses[$class['o:id']] = [
                    'vocabulary_id' => $class['o:vocabulary']['o:id'],
                    'local_name' => $class['o:local_name'],
                    'label' => $class['o:label'],
                    'count' => 0,
                ];
            }
            $query['page']++;
        }
        return $snapshotClasses;
    }

    /**
     * Set all remote vocabularies.
     *
     * @param string $rootEndpoint
     * @return array
     */
    protected function getSnapshotVocabularies($rootEndpoint)
    {
        $endpoint = sprintf('%s/vocabularies', $rootEndpoint);
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        $snapshotVocabularies = [];
        while (true) {
            $vocabularies = $this->getApiOutput($client, $query);
            if (!$vocabularies) {
                break; // No more vocabularies.
            }
            foreach ($vocabularies as $vocabulary) {
                $snapshotVocabularies[$vocabulary['o:id']] = [
                    'namespace_uri' => $vocabulary['o:namespace_uri'],
                    'label' => $vocabulary['o:label'],
                ];
            }
            $query['page']++;
        }
        return $snapshotVocabularies;
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
}
