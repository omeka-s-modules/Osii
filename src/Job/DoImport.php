<?php
namespace Osii\Job;

use DateTime;
use Exception;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Omeka\Entity as OmekaEntity;
use Osii\Stdlib\Mappings;

class DoImport extends AbstractOsiiJob
{
    protected $mappings;

    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        // Remove items that have been deleted or removed from the remote
        // installation since the previous snapshot.
        $dql = 'SELECT i.id AS osii_item, IDENTITY(i.localItem) AS local_item
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import
            AND i.remoteItemId NOT IN (:snapshotItems)';
        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameters([
                'import' => $this->getImportEntity(),
                'snapshotItems' => $this->getImportEntity()->getSnapshotItems(),
            ]);
        $itemsToDelete = $query->getResult();
        $osiiItemsToDelete = array_filter(array_column($itemsToDelete, 'osii_item'));
        $localItemsToDelete = array_filter(array_column($itemsToDelete, 'local_item'));
        $this->getApiManager()->batchDelete('osii_items', $osiiItemsToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->getApiManager()->batchDelete('items', $localItemsToDelete);
        }

        // Remove media that have been deleted or removed from the remote
        // installation since the previous snapshot.
        $dql = 'SELECT m.id AS osii_media, IDENTITY(m.localMedia) as local_media
            FROM Osii\Entity\OsiiMedia m
            WHERE m.import = :import
            AND m.remoteMediaId NOT IN (:snapshotMedia)';
        $query = $this->getEntityManager()->createQuery($dql)
        ->setParameters([
            'import' => $this->getImportEntity(),
            'snapshotMedia' => $this->getImportEntity()->getSnapshotMedia() ?: 0,
        ]);
        $mediaToDelete = $query->getResult();
        $osiiMediaToDelete = array_filter(array_column($mediaToDelete, 'osii_media'));
        $localMediaToDelete = array_filter(array_column($mediaToDelete, 'local_media'));
        $this->getApiManager()->batchDelete('osii_media', $osiiMediaToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->getApiManager()->batchDelete('media', $localMediaToDelete);
        }

        // Remove item sets that have been deleted or removed from the remote
        // installation since the previous snapshot.
        $dql = 'SELECT i.id AS osii_item_set, IDENTITY(i.localItemSet) AS local_item_set
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import
            AND i.remoteItemSetId NOT IN (:snapshotItemSets)';
        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameters([
                'import' => $this->getImportEntity(),
                'snapshotItemSets' => $this->getImportEntity()->getSnapshotItemSets() ?: 0,
            ]);
        $itemSetsToDelete = $query->getResult();
        $osiiItemSetsToDelete = array_filter(array_column($itemSetsToDelete, 'osii_item_set'));
        $localItemSetsToDelete = array_filter(array_column($itemSetsToDelete, 'local_item_set'));
        $this->getApiManager()->batchDelete('osii_item_sets', $osiiItemSetsToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->getApiManager()->batchDelete('item_sets', $localItemSetsToDelete);
        }

        // Create a local item stub for every remote item. We do this first so
        // we can correctly map resource values when updating the resource
        // metadata.
        $dql = 'SELECT i.id
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import
            AND i.localItem IS NULL';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $osiiItemIds = array_column($query->getResult(), 'id');
        $dql = 'SELECT i
            FROM Osii\Entity\OsiiItem i
            WHERE i.id IN (:osiiItemIds)';
        $query = $this->getEntityManager()->createQuery($dql);
        foreach (array_chunk($osiiItemIds, 100) as $osiiItemIdsChunk) {
            $query->setParameter('osiiItemIds', $osiiItemIdsChunk);
            foreach ($query->toIterable() as $osiiItemEntity) {
                $localItem = new OmekaEntity\Item;
                $localItem->setCreated(new DateTime('now'));
                $this->getEntityManager()->persist($localItem);
                $osiiItemEntity->setLocalItem($localItem);
            }
            $this->flushClear();
        }

        // Create a local item set stub for every remote item. We do this first
        // so we can correctly map resource values when updating the resource
        // metadata.
        $dql = 'SELECT i.id
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import
            AND i.localItemSet IS NULL';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $osiiItemSetIds = array_column($query->getResult(), 'id');
        $dql = 'SELECT i
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.id IN (:osiiItemSetIds)';
        $query = $this->getEntityManager()->createQuery($dql);
        foreach (array_chunk($osiiItemSetIds, 100) as $osiiItemSetIdsChunk) {
            $query->setParameter('osiiItemSetIds', $osiiItemSetIdsChunk);
            foreach ($query->toIterable() as $osiiItemSetEntity) {
                $localItemSet = new OmekaEntity\ItemSet;
                $localItemSet->setCreated(new DateTime('now'));
                $this->getEntityManager()->persist($localItemSet);
                $osiiItemSetEntity->setLocalItemSet($localItemSet);
            }
            $this->flushClear();
        }

        $this->mappings = new Mappings;

        // Set the user-configured data type and template maps.
        $this->mappings->set('dataTypes', $this->getImportEntity()->getDataTypeMap());
        $this->mappings->set('templates', $this->getImportEntity()->getTemplateMap());

        // Set the item map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT i.remoteItemId AS remote_item, IDENTITY(i.localItem) AS local_item
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $itemMap = array_column($query->getResult(), 'local_item', 'remote_item');
        $this->mappings->set('items', $itemMap);

        // Set the item set map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT i.remoteItemSetId AS remote_item_set, IDENTITY(i.localItemSet) AS local_item_set
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $itemSetMap = array_column($query->getResult(), 'local_item_set', 'remote_item_set');
        $this->mappings->set('itemSets', $itemSetMap);

        $snapshotVocabularies = $this->getImportEntity()->getSnapshotVocabularies();
        $snapshotProperties = $this->getImportEntity()->getSnapshotProperties();
        $snapshotClasses = $this->getImportEntity()->getSnapshotClasses();

        // Set the property map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT p.id AS property_id, CONCAT(v.namespaceUri, p.localName) AS uri
            FROM Omeka\Entity\Property p
            JOIN p.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $this->mappings->set('localProperties', array_column($query->getResult(), 'property_id', 'uri'));
        foreach ($snapshotProperties as $remotePropertyId => $remoteProperty) {
            $namespaceUri = $snapshotVocabularies[$remoteProperty['vocabulary_id']]['namespace_uri'];
            $localName = $remoteProperty['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            $localPropertyId = $this->mappings->get('localProperties', $uri);
            if ($localPropertyId) {
                $this->mappings->set('properties', $remotePropertyId, $localPropertyId);
            }
        }

        // Set the class map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT c.id AS class_id, CONCAT(v.namespaceUri, c.localName) AS uri
            FROM Omeka\Entity\ResourceClass c
            JOIN c.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $this->mappings->set('localClasses', array_column($query->getResult(), 'class_id', 'uri'));
        foreach ($snapshotClasses as $remoteClassId => $remoteClass) {
            $namespaceUri = $snapshotVocabularies[$remoteClass['vocabulary_id']]['namespace_uri'];
            $localName = $remoteClass['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            $localClassId = $this->mappings->get('localClasses', $uri);
            if ($localClassId) {
                $this->mappings->set('classes', $remoteClassId, $localClassId);
            }
        }

        // Import media from their snapshot. We must import media before other
        // resources because we need to populate the media map before updating
        // item values, specifically for mapping media resource values. Ideally,
        // we would create media stubs before updating them, like we do for
        // items and item sets, but media ingest only happens during create
        // operations, so that's not possible, and it's why we have to process
        // media first.
        $ingesterMapperManager = $this->getServiceLocator()->get('Osii\MediaIngesterMapperManager');
        $dql = 'SELECT m.id
            FROM Osii\Entity\OsiiMedia m
            WHERE m.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $osiiMediaIds = array_column($query->getResult(), 'id');
        $dql = 'SELECT m
            FROM Osii\Entity\OsiiMedia m
            WHERE m.id IN (:osiiMediaIds)';
        $query = $this->getEntityManager()->createQuery($dql);
        foreach (array_chunk($osiiMediaIds, 20) as $osiiMediaIdsChunk) {
            $this->logIds($osiiMediaIdsChunk, 'Iterating OSII media');
            $query->setParameter('osiiMediaIds', $osiiMediaIdsChunk);
            foreach ($query->toIterable() as $osiiMediaEntity) {
                $localMediaEntity = $osiiMediaEntity->getLocalMedia();
                $localItemEntity = $osiiMediaEntity->getOsiiItem()->getLocalItem();
                $remoteMedia = $osiiMediaEntity->getSnapshotMedia();
                $localMedia = [];
                try {
                    $ingesterMapper = $ingesterMapperManager->get(
                        $remoteMedia['o:ingester'],
                        ['importEntity' => $this->getImportEntity()]
                    );
                } catch (ServiceNotFoundException $e) {
                    // Ingester mapper is not on local installation. Log the URL
                    // to the remote media representation and continue to the
                    // next media.
                    $this->getLogger()->err(sprintf(
                        "Cannot import remote media (no ingester mapper): %s/media/%s\n%s",
                        $this->getImportEntity()->getRootEndpoint(),
                        $osiiMediaEntity->getRemoteMediaId(),
                        (string) $e,
                    ));
                    continue;
                }
                $localMedia = $this->mapOwner($localMedia, $remoteMedia);
                $localMedia = $this->mapVisibility($localMedia, $remoteMedia);
                $localMedia = $this->mapClass($localMedia, $remoteMedia);
                $localMedia = $this->mapTemplate($localMedia, $remoteMedia);
                $localMedia = $this->mapValues($localMedia, $remoteMedia);
                $localMedia = $this->addSourceUrls($localMedia, $remoteMedia, 'media');
                $localMedia['position'] = $osiiMediaEntity->getPosition();
                if ($localMediaEntity) {
                    // Local media exists. Update the media.
                    $localMedia = $ingesterMapper->mapForUpdate($localMedia, $remoteMedia);
                    $this->getApiManager()->update('media', $localMediaEntity->getId(), $localMedia);
                } else {
                    // Local media does not exist. Create the media.
                    $localMedia = $ingesterMapper->mapForCreate($localMedia, $remoteMedia);
                    $localMedia['o:item'] = ['o:id' => $localItemEntity->getId()];
                    $createOptions = [
                        'responseContent' => 'resource', // Get the entity so we can assign it to the OSII media.
                    ];
                    try {
                        $localMediaEntity = $this->getApiManager()->create('media', $localMedia, [], $createOptions)->getContent();
                    } catch (Exception $e) {
                        // There was an error when importing the media. Log the URL
                        // to the remote media representation and continue to the
                        // next media.
                        $this->getLogger()->err(sprintf(
                            "Cannot import remote media (error during create): %s/media/%s\n%s",
                            $this->getImportEntity()->getRootEndpoint(),
                            $osiiMediaEntity->getRemoteMediaId(),
                            (string) $e,
                        ));
                        continue;
                    }
                    $osiiMediaEntity->setLocalMedia($localMediaEntity);
                }
                $this->mappings->set('media', $remoteMedia['o:id'], $localMediaEntity->getId());
            }
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        // Import items from their snapshot.
        $dql = 'SELECT i.id
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $osiiItemIds = array_column($query->getResult(), 'id');
        $dql = 'SELECT i
            FROM Osii\Entity\OsiiItem i
            WHERE i.id IN (:osiiItemIds)';
        $query = $this->getEntityManager()->createQuery($dql);
        foreach (array_chunk($osiiItemIds, 100) as $osiiItemIdsChunk) {
            $this->logIds($osiiItemIdsChunk, 'Iterating OSII items');
            $query->setParameter('osiiItemIds', $osiiItemIdsChunk);
            foreach ($query->toIterable() as $osiiItemEntity) {
                $localItemEntity = $osiiItemEntity->getLocalItem();
                $remoteItem = $osiiItemEntity->getSnapshotItem();
                $localItem = [];
                $localItem = $this->mapOwner($localItem, $remoteItem);
                $localItem = $this->mapVisibility($localItem, $remoteItem);
                $localItem = $this->mapClass($localItem, $remoteItem);
                $localItem = $this->mapTemplate($localItem, $remoteItem);
                $localItem = $this->mapValues($localItem, $remoteItem);
                $localItem = $this->addSourceUrls($localItem, $remoteItem, 'items');
                // Map remote to local media. Media has already been imported
                // above, but this step is still necessary to remove media added
                // locally since the last import.
                $localItem['o:media'] = [];
                foreach ($remoteItem['o:media'] as $remoteMedia) {
                    $mediaId = $this->mappings->get('media', $remoteMedia['o:id']);
                    if ($mediaId) {
                        $localItem['o:media'][] = ['o:id' => $mediaId];
                    }
                }
                // Map remote to local item sets. Set an empty o:item_set by
                // default to remove item sets added locally since the last
                // import.
                $localItem['o:item_set'] = [];
                foreach ($remoteItem['o:item_set'] as $remoteItemSet) {
                    $itemSetId = $this->mappings->get('itemSets', $remoteItemSet['o:id']);
                    if ($itemSetId) {
                        $localItem['o:item_set'][] = ['o:id' => $itemSetId];
                    }
                }
                // Add the import's local item set.
                $localItemSet = $this->getImportEntity()->getLocalItemSet();
                if ($localItemSet) {
                    $localItem['o:item_set'][] = ['o:id' => $localItemSet->getId()];
                }
                $updateOptions = [
                    'flushEntityManager' => false, // Flush (and clear) only once per batch.
                    'responseContent' => 'resource', // Avoid the overhead of composing the representation.
                    'isPartial' => true, // Declare a partial (PATCH) update so media is not deleted.
                ];
                $this->getApiManager()->update('items', $localItemEntity->getId(), $localItem, [], $updateOptions);
            }
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        // Import item sets from their snapshot.
        $dql = 'SELECT i.id
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $osiiItemSetIds = array_column($query->getResult(), 'id');
        $dql = 'SELECT i
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.id IN (:osiiItemSetIds)';
        $query = $this->getEntityManager()->createQuery($dql);
        foreach (array_chunk($osiiItemSetIds, 100) as $osiiItemSetIdsChunk) {
            $this->logIds($osiiItemSetIdsChunk, 'Iterating OSII item sets');
            $query->setParameter('osiiItemSetIds', $osiiItemSetIdsChunk);
            foreach ($query->toIterable() as $osiiItemSetEntity) {
                $localItemSetEntity = $osiiItemSetEntity->getLocalItemSet();
                $remoteItemSet = $osiiItemSetEntity->getSnapshotItemSet();
                $localItemSet = [];
                $localItemSet = $this->mapOwner($localItemSet, $remoteItemSet);
                $localItemSet = $this->mapVisibility($localItemSet, $remoteItemSet);
                $localItemSet = $this->mapClass($localItemSet, $remoteItemSet);
                $localItemSet = $this->mapTemplate($localItemSet, $remoteItemSet);
                $localItemSet = $this->mapValues($localItemSet, $remoteItemSet);
                $localItemSet = $this->addSourceUrls($localItemSet, $remoteItemSet, 'item_sets');
                $updateOptions = [
                    'flushEntityManager' => false, // Flush (and clear) only once per batch.
                    'responseContent' => 'resource', // Avoid the overhead of composing the representation.
                    'isPartial' => true, // Declare a partial (PATCH) update so item associations are not deleted.
                ];
                $this->getApiManager()->update('item_sets', $localItemSetEntity->getId(), $localItemSet, [], $updateOptions);
            }
            $this->flushClear();
            if ($this->shouldStop()) {
                return;
            }
        }

        $this->getImportEntity()->setImportCompleted(new DateTime('now'));
        $this->flushClear();
    }

    /**
     * Map remote to local owner.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array The local resource JSON-LD
     */
    protected function mapOwner(array $localResource, array $remoteResource)
    {
        $localResource['o:owner']['o:id'] = $this->job->getOwner()->getId();
        return $localResource;
    }

    /**
     * Map remote to local visibility.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array The local resource JSON-LD
     */
    protected function mapVisibility(array $localResource, array $remoteResource)
    {
        $localResource['o:is_public'] = $remoteResource['o:is_public'];
        return $localResource;
    }

    /**
     * Map remote to local resource class.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array The local resource JSON-LD
     */
    protected function mapClass(array $localResource, array $remoteResource)
    {
        if (isset($remoteResource['o:resource_class'])
            && $this->mappings->get('classes', $remoteResource['o:resource_class']['o:id'])
        ) {
            $localResource['o:resource_class']['o:id'] = $this->mappings->get('classes', $remoteResource['o:resource_class']['o:id']);
        }
        return $localResource;
    }

    /**
     * Map remote to local resource template.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array The local resource JSON-LD
     */
    protected function mapTemplate(array $localResource, array $remoteResource)
    {
        if (isset($remoteResource['o:resource_template'])
            && $this->mappings->get('templates', $remoteResource['o:resource_template']['o:id'])
        ) {
            $localResource['o:resource_template']['o:id'] = $this->mappings->get('templates', $remoteResource['o:resource_template']['o:id']);
        }
        return $localResource;
    }

    /**
     * Map remote to local values.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array The local resource JSON-LD
     */
    protected function mapValues(array $localResource, array $remoteResource)
    {
        foreach ($this->getValuesFromResource($remoteResource) as $remoteValue) {
            $localDataTypeId = $this->mappings->get('dataTypes', $remoteValue['type']);
            if (!$localDataTypeId) {
                // Data type is not on local installation. Ignore value.
                continue;
            }
            $localPropertyId = $this->mappings->get('properties', $remoteValue['property_id']);
            if (!$localPropertyId) {
                // Property is not on local installation. Ignore value.
                continue;
            }
            if (isset($remoteValue['value_resource_id'])) {
                if ('items' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $this->mappings->get('items', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Item is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                } elseif ('item_sets' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $this->mappings->get('itemSets', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Item set is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                } elseif ('media' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $this->mappings->get('media', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Media is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                }
            }
            $remoteValue['type'] = $localDataTypeId;
            $remoteValue['property_id'] = $localPropertyId;
            if (!isset($localResource[$localPropertyId])) {
                $localResource[$localPropertyId] = [];
            }
            $localResource[$localPropertyId][] = $remoteValue;
        }
        return $localResource;
    }

    /**
     * Add source URL values.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @param string $resourceType items, item_sets, or media
     * @return array The local resource JSON-LD
     */
    public function addSourceUrls(array $localResource, array $remoteResource, $resourceType)
    {
        // Add the source resource value.
        $sourceResourcePropertyId = $this->mappings->get(
            'localProperties',
            'http://omeka.org/s/vocabs/o-module-osii#source_resource'
        );
        if ($sourceResourcePropertyId && $this->getImportEntity()->getAddSourceResource()) {
            $localResource[$sourceResourcePropertyId][] = [
                'type' => 'uri',
                'property_id' => $sourceResourcePropertyId,
                '@id' => sprintf(
                    '%s/%s/%s',
                    $this->getImportEntity()->getRootEndpoint(),
                    $resourceType,
                    $remoteResource['o:id']
                ),
            ];
        }
        // Add the source site value.
        $sourceSitePropertyId = $this->mappings->get(
            'localProperties',
            'http://omeka.org/s/vocabs/o-module-osii#source_site'
        );
        if ($sourceSitePropertyId && $this->getImportEntity()->getSourceSite()) {
            $localResource[$sourceSitePropertyId][] = [
                'type' => 'uri',
                'property_id' => $sourceSitePropertyId,
                '@id' => $this->getImportEntity()->getSourceSite(),
            ];
        }
        return $localResource;
    }
}
