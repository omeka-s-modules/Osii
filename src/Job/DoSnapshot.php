<?php
namespace Osii\Job;

use Composer\Semver\Comparator;
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
        $snapshotMediaIngesters = [];
        $rootEndpoint = $this->getImportEntity()->getRootEndpoint();
        $snapshotDataTypes = $this->getSnapshotDataTypes($rootEndpoint);
        $snapshotProperties = $this->getSnapshotProperties($rootEndpoint);
        $snapshotClasses = $this->getSnapshotClasses($rootEndpoint);
        $snapshotVocabularies = $this->getSnapshotVocabularies($rootEndpoint);
        $snapshotTemplates = $this->getSnapshotTemplates($rootEndpoint);

        // Iterate remote items.
        $endpoint = sprintf('%s/items', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        parse_str($this->getImportEntity()->getRemoteQuery(), $query);
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
                $item = $this->prepareResource($item);
                $osiiItemEntity->setSnapshotItem($item);

                // Set metadata about the snapshot.
                $snapshotItems[] = $item['o:id'];
                if (!empty($item['o:media']) && !$this->getImportEntity()->getExcludeMedia()) {
                    foreach ($item['o:media'] as $media) {
                        $snapshotMedia[] = $media['o:id'];
                    }
                }
                if (!empty($item['o:item_set']) && !$this->getImportEntity()->getExcludeItemSets()) {
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
                if (isset($item['o:resource_template'])) {
                    $templateId = $item['o:resource_template']['o:id'];
                    ++$snapshotTemplates[$templateId]['count'];
                }
                // Set metadata about resource values.
                foreach ($this->getValuesFromResource($item) as $value) {
                    $dataTypeId = $value['type'];
                    $propertyId = $value['property_id'];
                    if (!isset($snapshotDataTypes[$dataTypeId])) {
                        $snapshotDataTypes[$dataTypeId] = [
                            'label' => null,
                            'count' => 0,
                        ];
                    }
                    ++$snapshotDataTypes[$dataTypeId]['count'];
                    ++$snapshotProperties[$propertyId]['count'];
                    // Set metadata about value annotations.
                    foreach ($this->getValueAnnotationsFromValue($value) as $valueAnnotation) {
                        $dataTypeId = $valueAnnotation['type'];
                        $propertyId = $valueAnnotation['property_id'];
                        if (!isset($snapshotDataTypes[$dataTypeId])) {
                            $snapshotDataTypes[$dataTypeId] = [
                                'label' => null,
                                'count' => 0,
                            ];
                        }
                        ++$snapshotDataTypes[$dataTypeId]['count'];
                        ++$snapshotProperties[$propertyId]['count'];
                    }
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
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 50,
            'page' => 1,
        ];
        // Iterate only if there are media to snapshot.
        while ($snapshotMedia) {
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
                $media = $this->prepareResource($media);
                $osiiMediaEntity->setSnapshotMedia($media);

                // Set metadata about the snapshot.
                if (isset($media['o:resource_class'])) {
                    $classId = $media['o:resource_class']['o:id'];
                    ++$snapshotClasses[$classId]['count'];
                }
                if (isset($item['o:resource_template'])) {
                    $templateId = $item['o:resource_template']['o:id'];
                    ++$snapshotTemplates[$templateId]['count'];
                }
                // Set metadata about resource values.
                foreach ($this->getValuesFromResource($media) as $value) {
                    $dataTypeId = $value['type'];
                    $propertyId = $value['property_id'];
                    if (!isset($snapshotDataTypes[$dataTypeId])) {
                        $snapshotDataTypes[$dataTypeId] = [
                            'label' => null,
                            'count' => 0,
                        ];
                    }
                    ++$snapshotDataTypes[$dataTypeId]['count'];
                    ++$snapshotProperties[$propertyId]['count'];
                    // Set metadata about value annotations.
                    foreach ($this->getValueAnnotationsFromValue($value) as $valueAnnotation) {
                        $dataTypeId = $valueAnnotation['type'];
                        $propertyId = $valueAnnotation['property_id'];
                        if (!isset($snapshotDataTypes[$dataTypeId])) {
                            $snapshotDataTypes[$dataTypeId] = [
                                'label' => null,
                                'count' => 0,
                            ];
                        }
                        ++$snapshotDataTypes[$dataTypeId]['count'];
                        ++$snapshotProperties[$propertyId]['count'];
                    }
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
            'sort_by' => 'id',
            'sort_order' => 'asc',
            'per_page' => 50,
            'page' => 1,
        ];
        // Iterate only if there are item sets to snapshot.
        while ($snapshotItemSets) {
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
                $itemSet = $this->prepareResource($itemSet);
                $osiiItemSetEntity->setSnapshotItemSet($itemSet);

                // Set metadata about the snapshot.
                if (isset($itemSet['o:resource_class'])) {
                    $classId = $itemSet['o:resource_class']['o:id'];
                    ++$snapshotClasses[$classId]['count'];
                }
                if (isset($item['o:resource_template'])) {
                    $templateId = $item['o:resource_template']['o:id'];
                    ++$snapshotTemplates[$templateId]['count'];
                }
                // Set metadata about resource values.
                foreach ($this->getValuesFromResource($itemSet) as $value) {
                    $dataTypeId = $value['type'];
                    $propertyId = $value['property_id'];
                    if (!isset($snapshotDataTypes[$dataTypeId])) {
                        $snapshotDataTypes[$dataTypeId] = [
                            'label' => null,
                            'count' => 0,
                        ];
                    }
                    ++$snapshotDataTypes[$dataTypeId]['count'];
                    ++$snapshotProperties[$propertyId]['count'];
                    // Set metadata about value annotations.
                    foreach ($this->getValueAnnotationsFromValue($value) as $valueAnnotation) {
                        $dataTypeId = $valueAnnotation['type'];
                        $propertyId = $valueAnnotation['property_id'];
                        if (!isset($snapshotDataTypes[$dataTypeId])) {
                            $snapshotDataTypes[$dataTypeId] = [
                                'label' => null,
                                'count' => 0,
                            ];
                        }
                        ++$snapshotDataTypes[$dataTypeId]['count'];
                        ++$snapshotProperties[$propertyId]['count'];
                    }
                }
            }
            $query['page']++;
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        // Remove extraneous data types, properties, classes, and templates.
        $snapshotDataTypes = array_filter($snapshotDataTypes, function ($dataType) {
            return $dataType['count'];
        });
        $snapshotProperties = array_filter($snapshotProperties, function ($property) {
            return $property['count'];
        });
        $snapshotClasses = array_filter($snapshotClasses, function ($class) {
            return $class['count'];
        });
        $snapshotTemplates = array_filter($snapshotTemplates, function ($template) {
            return $template['count'];
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
        $this->getImportEntity()->setSnapshotTemplates($snapshotTemplates);
        $this->getImportEntity()->setSnapshotCompleted(new DateTime('now'));

        $this->flushClear();
    }

    /**
     * Prepare remote resource data.
     *
     * @param array $remoteResource
     * @return array
     */
    public function prepareResource(array $remoteResource)
    {
        $resourceMapperManager = $this->getServiceLocator()->get('Osii\ResourceMapperManager');
        foreach ($resourceMapperManager->getRegisteredNames() as $resourceMapperName) {
            $resourceMapper = $resourceMapperManager->get($resourceMapperName, ['job' => $this]);
            $remoteResource = $resourceMapper->prepareResource($remoteResource);
        }
        return $remoteResource;
    }

    /**
     * Set all remote data types.
     *
     * @param string $rootEndpoint
     * @return array
     */
    public function getSnapshotDataTypes($rootEndpoint)
    {
        $snapshotDataTypes = [];
        // Note that the data_types resource was not available until v3.2.0.
        if (!Comparator::greaterThanOrEqualTo($this->getRemoteVersion(), '3.2.0')) {
            return $snapshotDataTypes;
        }
        // Note that the data_types endpoint does not paginate, so there's no
        // need to iterate pages.
        $endpoint = sprintf('%s/data_types', $rootEndpoint);
        $client = $this->getApiClient($endpoint);
        $dataTypes = $this->getApiOutput($client, []);
        foreach ($dataTypes as $dataType) {
            $snapshotDataTypes[$dataType['o:id']] = [
                'label' => $dataType['o:label'],
                'count' => 0,
            ];
        }
        return $snapshotDataTypes;
    }

    /**
     * Set all remote properties.
     *
     * @param string $rootEndpoint
     * @return array
     */
    public function getSnapshotProperties($rootEndpoint)
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
    public function getSnapshotClasses($rootEndpoint)
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
    public function getSnapshotVocabularies($rootEndpoint)
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
     * Set all remote templates.
     *
     * @param string $rootEndpoint
     * @return array
     */
    public function getSnapshotTemplates($rootEndpoint)
    {
        $endpoint = sprintf('%s/resource_templates', $rootEndpoint);
        $client = $this->getApiClient($endpoint);
        $query['per_page'] = 50;
        $query['page'] = 1;
        $snapshotTemplates = [];
        while (true) {
            $templates = $this->getApiOutput($client, $query);
            if (!$templates) {
                break; // No more template.
            }
            foreach ($templates as $template) {
                $snapshotTemplates[$template['o:id']] = [
                    'label' => $template['o:label'],
                    'count' => 0,
                ];
            }
            $query['page']++;
        }
        return $snapshotTemplates;
    }

    /**
     * Get the API client.
     *
     * @param string $endpoint
     * @return Client
     */
    public function getApiClient($endpoint)
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
    public function getApiOutput(Client $client, array $query)
    {
        $query['key_identity'] = $this->getImportEntity()->getKeyIdentity();
        $query['key_credential'] = $this->getImportEntity()->getKeyCredential();
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

    /**
     * Get the version of the remote Omeka installation.
     *
     * @return string
     */
    public function getRemoteVersion()
    {
        $endpoint = sprintf('%s/items', $this->getImportEntity()->getRootEndpoint());
        $client = $this->getApiClient($endpoint);
        $response = $client->send();
        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException(sprintf('Cannot resolve API endpoint: %s', $endpoint));
        }
        $versionHeader = $response->getHeaders()->get('omeka-s-version');
        if (!$versionHeader) {
            throw new Exception\RuntimeException(sprintf('Not an Omeka S endpoint: %s', $endpoint));
        }
        return $versionHeader->getFieldValue();
    }
}
