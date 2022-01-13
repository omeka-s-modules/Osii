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

        $this->mappings = new Mappings;

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
        $this->batchDelete('osii_items', $osiiItemsToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->batchDelete('items', $localItemsToDelete);
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
        $this->batchDelete('osii_media', $osiiMediaToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->batchDelete('media', $localMediaToDelete);
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
        $this->batchDelete('osii_item_sets', $osiiItemSetsToDelete);
        if (!$this->getImportEntity()->getKeepRemovedResources()) {
            $this->batchDelete('item_sets', $localItemSetsToDelete);
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

        // Set the user-configured data type and template maps.
        $this->getMappings()->set('dataTypes', $this->getImportEntity()->getDataTypeMap());
        $this->getMappings()->set('templates', $this->getImportEntity()->getTemplateMap());

        // Set the item map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT i.remoteItemId AS remote_item, IDENTITY(i.localItem) AS local_item
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $itemMap = array_column($query->getResult(), 'local_item', 'remote_item');
        $this->getMappings()->set('items', $itemMap);

        // Set the item set map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT i.remoteItemSetId AS remote_item_set, IDENTITY(i.localItemSet) AS local_item_set
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import';
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('import', $this->getImportEntity());
        $itemSetMap = array_column($query->getResult(), 'local_item_set', 'remote_item_set');
        $this->getMappings()->set('itemSets', $itemSetMap);

        $snapshotVocabularies = $this->getImportEntity()->getSnapshotVocabularies();
        $snapshotProperties = $this->getImportEntity()->getSnapshotProperties();
        $snapshotClasses = $this->getImportEntity()->getSnapshotClasses();

        // Set the property map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT p.id AS property_id, CONCAT(v.namespaceUri, p.localName) AS uri
            FROM Omeka\Entity\Property p
            JOIN p.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $this->getMappings()->set('localProperties', array_column($query->getResult(), 'property_id', 'uri'));
        foreach ($snapshotProperties as $remotePropertyId => $remoteProperty) {
            $namespaceUri = $snapshotVocabularies[$remoteProperty['vocabulary_id']]['namespace_uri'];
            $localName = $remoteProperty['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            $localPropertyId = $this->getMappings()->get('localProperties', $uri);
            if ($localPropertyId) {
                $this->getMappings()->set('properties', $remotePropertyId, $localPropertyId);
            }
        }

        // Set the class map. Keys are remote IDs. Values are local IDs.
        $dql = 'SELECT c.id AS class_id, CONCAT(v.namespaceUri, c.localName) AS uri
            FROM Omeka\Entity\ResourceClass c
            JOIN c.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $this->getMappings()->set('localClasses', array_column($query->getResult(), 'class_id', 'uri'));
        foreach ($snapshotClasses as $remoteClassId => $remoteClass) {
            $namespaceUri = $snapshotVocabularies[$remoteClass['vocabulary_id']]['namespace_uri'];
            $localName = $remoteClass['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            $localClassId = $this->getMappings()->get('localClasses', $uri);
            if ($localClassId) {
                $this->getMappings()->set('classes', $remoteClassId, $localClassId);
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
                        (string) $e
                    ));
                    continue;
                }
                $localMedia = [
                    'o:id' => $localMediaEntity ? $localMediaEntity->getId() : null,
                ];
                $localMedia = $this->mapResource($localMedia, $remoteMedia);
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
                            (string) $e
                        ));
                        continue;
                    }
                    $osiiMediaEntity->setLocalMedia($localMediaEntity);
                }
                $this->getMappings()->set('media', $remoteMedia['o:id'], $localMediaEntity->getId());
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
                $localItem = [
                    'o:id' => $localItemEntity->getId(),
                ];
                $localItem = $this->mapResource($localItem, $remoteItem);
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
                $localItemSet = [
                    'o:id' => $localItemSetEntity->getId(),
                ];
                $localItemSet = $this->mapResource($localItemSet, $remoteItemSet);
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
     * Get the mappings for this import.
     *
     * @return Osii\Stdlib\Mappings
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * Map remote to to local resource data
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array
     */
    public function mapResource(array $localResource, array $remoteResource)
    {
        $resourceMapperManager = $this->getServiceLocator()->get('Osii\ResourceMapperManager');
        foreach ($resourceMapperManager->getRegisteredNames() as $resourceMapperName) {
            $resourceMapper = $resourceMapperManager->get($resourceMapperName, ['job' => $this]);
            $localResource = $resourceMapper->mapResource($localResource, $remoteResource);
        }
        return $localResource;
    }

    /**
     * Perform a batch delete operation.
     *
     * When deleting a very large amount of resources, the batch delete
     * operation may exceed the memory limit. This method prevents that by
     * chunking the resource IDs into managable sizes.
     *
     * @param string $resourceName
     * @param array $resourceIds
     */
    public function batchDelete($resourceName, array $resourceIds)
    {
        foreach (array_chunk($resourceIds, 1000) as $resourceIdsChunk) {
            $this->getApiManager()->batchDelete($resourceName, $resourceIdsChunk);
        }
    }
}
