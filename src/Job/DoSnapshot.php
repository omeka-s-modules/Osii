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
        $snapshotItemSets = [];
        $snapshotDataTypes = [];
        $snapshotMediaIngesters = [];
        $rootEndpoint = $this->getImportEntity()->getRootEndpoint();
        $snapshotProperties = $this->getSnapshotProperties($rootEndpoint);
        $snapshotClasses = $this->getSnapshotClasses($rootEndpoint);
        $snapshotVocabularies = $this->getSnapshotVocabularies($rootEndpoint);

        $remoteMediaPositions = [];

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
            $items = $this->getApiOutput($client, $query);
            if (!$items) {
                break; // No more items.
            }
            $this->logIds($items, 'Iterating remote items');
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
                        $snapshotMedia[] = $media['o:id'];
                        $remoteMediaPositions[$media['o:id']] = $position + 1;
                    }
                }
                if (!empty($item['o:item_set'])) {
                    foreach ($item['o:item_set'] as $itemSet) {
                        if (!in_array($itemSet['o:id'], $snapshotItemSets)) {
                            $snapshotItemSets[] = $itemSet['o:id'];
                        }
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
            $query['page']++;
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        // Iterate remote media. This will iterate every media in the remote
        // installation and make snapshots of those that belong to items in the
        // import, ignoring the rest. For most circumstances, this method is
        // much faster than the alternative of making a media request for every
        // item.
        $endpoint = sprintf('%s/media', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query = [
            'key_identity' => $this->getImportEntity()->getKeyIdentity(),
            'key_credential' => $this->getImportEntity()->getKeyCredential(),
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 50,
            'page' => 1,
        ];
        while (true) {
            $medias = $this->getApiOutput($client, $query);
            if (!$medias) {
                break; // No more media.
            }
            $this->logIds($medias, 'Iterating remote media');
            foreach ($medias as $media) {
                if (!in_array($media['o:id'], $snapshotMedia)) {
                    continue; // This media is not part of the import.
                }
                // Save snapshots of remote media.
                $osiiMediaEntity = $this->getEntityManager()
                    ->getRepository(OsiiEntity\OsiiMedia::class)
                    ->findOneBy([
                        'import' => $this->getImportEntity(),
                        'remoteMediaId' => $media['o:id'],
                    ]);
                if (null === $osiiMediaEntity) {
                    // This is a new remote media.
                    $osiiItemEntity = $this->getEntityManager()
                        ->getRepository(OsiiEntity\OsiiItem::class)
                        ->findOneBy([
                            'import' => $this->getImportEntity(),
                            'remoteItemId' => $media['o:item']['o:id'],
                        ]);
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
                $osiiMediaEntity->setPosition($remoteMediaPositions[$media['o:id']]);

                // Set metadata about the snapshot.
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
            $query['page']++;
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        // Iterate remote item sets. This will iterate every item set in the
        // remote installation and make snapshots of those that belong to items
        // in the import, ignoring the rest. For most circumstances, this method
        // is much faster than the alternative of making a media request for
        // every item.
        $endpoint = sprintf('%s/item_sets', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $query = [
            'key_identity' => $this->getImportEntity()->getKeyIdentity(),
            'key_credential' => $this->getImportEntity()->getKeyCredential(),
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 50,
            'page' => 1,
        ];
        while (true) {
            $itemSets = $this->getApiOutput($client, $query);
            if (!$itemSets) {
                break; // No more item sets.
            }
            $this->logIds($itemSets, 'Iterating remote item sets');
            foreach ($itemSets as $itemSet) {
                if (!in_array($itemSet['o:id'], $snapshotItemSets)) {
                    continue; // This item set is not part of the import.
                }
                // Save snapshots of remote item sets.
                $osiiItemSetEntity = $this->getEntityManager()
                    ->getRepository(OsiiEntity\OsiiItemSet::class)
                    ->findOneBy([
                        'import' => $this->getImportEntity(),
                        'remoteItemSetId' => $itemSet['o:id'],
                    ]);
                if (null === $osiiItemSetEntity) {
                    // This is a new remote item set.
                    $osiiItemSetEntity = new OsiiEntity\OsiiItemSet;
                    $osiiItemSetEntity->setImport($this->getImportEntity());
                    $osiiItemSetEntity->setRemoteItemSetId($itemSet['o:id']);
                    $this->getEntityManager()->persist($osiiItemSetEntity);
                } else {
                    // This is an existing remote item set.
                    $osiiItemSetEntity->setModified(new DateTime('now'));
                }
                $osiiItemSetEntity->setSnapshotItemSet($itemSet);

                // Set metadata about the snapshot.
                if (isset($itemSet['o:resource_class'])) {
                    $classId = $itemSet['o:resource_class']['o:id'];
                    ++$snapshotClasses[$classId]['count'];
                }
                foreach ($this->getValuesFromResource($itemSet) as $value) {
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
            $query['page']++;
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
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
        $this->getImportEntity()->setSnapshotItemSets($snapshotItemSets);
        $this->getImportEntity()->setSnapshotDataTypes($snapshotDataTypes);
        $this->getImportEntity()->setSnapshotMediaIngesters($snapshotMediaIngesters);
        $this->getImportEntity()->setSnapshotProperties($snapshotProperties);
        $this->getImportEntity()->setSnapshotClasses($snapshotClasses);
        $this->getImportEntity()->setSnapshotVocabularies($snapshotVocabularies);
        $this->getImportEntity()->setSnapshotCompleted(new DateTime('now'));

        $this->flushClear();
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
            throw new Exception\RuntimeException(sprintf('Cannot resolve API endpoint: %s', $client->getUri()->toString()));
        }
        if (!$response->getHeaders()->get('omeka-s-version')) {
            throw new Exception\RuntimeException(sprintf('Not an Omeka S endpoint: %s', $client->getUri()->toString()));
        }
        $output = json_decode($response->getBody(), true);
        return $output;
    }
}
