<?php
namespace Osii\Job;

use DateTime;
use Exception;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Omeka\Entity as OmekaEntity;

class DoImport extends AbstractOsiiJob
{
    protected $itemMap;
    protected $itemSetMap;
    protected $propertyMap;
    protected $classMap;
    protected $dataTypeMap;

    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        // Remote resources may have been deleted (or simply removed) since the
        // previous snapshot. These must be deleted locally so that the local
        // resources are in sync with the remote ones. Here we get these removed
        // resources and delete them. Note that not all OSII resources will have
        // related Omeka resources becuase snapshots can change without a
        // subsequent import.
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
        if ($this->getImportEntity()->getDeleteRemovedItems()) {
            $this->getApiManager()->batchDelete('items', $localItemsToDelete);
        }

        $dql = 'SELECT m.id AS osii_media, IDENTITY(m.localMedia) as local_media
            FROM Osii\Entity\OsiiMedia m
            WHERE m.import = :import
            AND m.remoteMediaId NOT IN (:snapshotMedia)';
        $query = $this->getEntityManager()->createQuery($dql)
        ->setParameters([
            'import' => $this->getImportEntity(),
            'snapshotMedia' => $this->getImportEntity()->getSnapshotMedia(),
        ]);
        $mediaToDelete = $query->getResult();
        $osiiMediaToDelete = array_filter(array_column($mediaToDelete, 'osii_media'));
        $localMediaToDelete = array_filter(array_column($mediaToDelete, 'local_media'));
        $this->getApiManager()->batchDelete('osii_media', $osiiMediaToDelete);
        if ($this->getImportEntity()->getDeleteRemovedMedia()) {
            $this->getApiManager()->batchDelete('media', $localMediaToDelete);
        }

        $dql = 'SELECT i.id AS osii_item_set, IDENTITY(i.localItemSet) AS local_item_set
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import
            AND i.remoteItemSetId NOT IN (:snapshotItemSets)';
        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameters([
                'import' => $this->getImportEntity(),
                'snapshotItemSets' => $this->getImportEntity()->getSnapshotItemSets(),
            ]);
        $itemSetsToDelete = $query->getResult();
        $osiiItemSetsToDelete = array_filter(array_column($itemSetsToDelete, 'osii_item_set'));
        $localItemSetsToDelete = array_filter(array_column($itemSetsToDelete, 'local_item_set'));
        $this->getApiManager()->batchDelete('osii_item_sets', $osiiItemSetsToDelete);
        if ($this->getImportEntity()->getDeleteRemovedItemSets()) {
            $this->getApiManager()->batchDelete('item_sets', $localItemSetsToDelete);
        }

        // Create a local resource for every remote resource (except media). We
        // do this first so we can correctly map resource values when updating
        // the resource metadata.
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

        // Get the remote/local ID maps. Keys are remote IDs; values are local
        // IDs. Here we're mapping the remote items, properties, and classes to
        // the local items, properties, and classes.
        $dql = 'SELECT i.remoteItemId AS remote_item, IDENTITY(i.localItem) AS local_item
            FROM Osii\Entity\OsiiItem i
            WHERE i.import = :import';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('import', $this->getImportEntity());
        $this->itemMap = array_column($query->getResult(), 'local_item', 'remote_item');

        $dql = 'SELECT i.remoteItemSetId AS remote_item_set, IDENTITY(i.localItemSet) AS local_item_set
            FROM Osii\Entity\OsiiItemSet i
            WHERE i.import = :import';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('import', $this->getImportEntity());
        $this->itemSetMap = array_column($query->getResult(), 'local_item_set', 'remote_item_set');

        $dql = 'SELECT p.id AS property_id, CONCAT(v.namespaceUri, p.localName) AS uri
            FROM Omeka\Entity\Property p
            JOIN p.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $localProperties = array_column($query->getResult(), 'property_id', 'uri');

        $dql = 'SELECT c.id AS class_id, CONCAT(v.namespaceUri, c.localName) AS uri
            FROM Omeka\Entity\ResourceClass c
            JOIN c.vocabulary v';
        $query = $this->getEntityManager()->createQuery($dql);
        $localClasses = array_column($query->getResult(), 'class_id', 'uri');

        $snapshotVocabularies = $this->getImportEntity()->getSnapshotVocabularies();
        $snapshotProperties = $this->getImportEntity()->getSnapshotProperties();
        $snapshotClasses = $this->getImportEntity()->getSnapshotClasses();

        $this->propertyMap = [];
        foreach ($snapshotProperties as $remotePropertyId => $remoteProperty) {
            $namespaceUri = $snapshotVocabularies[$remoteProperty['vocabulary_id']]['namespace_uri'];
            $localName = $remoteProperty['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            if (isset($localProperties[$uri])) {
                $this->propertyMap[$remotePropertyId] = $localProperties[$uri];
            }
        }

        $this->classMap = [];
        foreach ($snapshotClasses as $remoteClassId => $remoteClass) {
            $namespaceUri = $snapshotVocabularies[$remoteClass['vocabulary_id']]['namespace_uri'];
            $localName = $remoteClass['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            if (isset($localClasses[$uri])) {
                $this->classMap[$remoteClassId] = $localClasses[$uri];
            }
        }

        $this->dataTypeMap = $this->getImportEntity()->getDataTypeMap();

        $sourceItemPropertyId = $localProperties['http://omeka.org/s/vocabs/o-module-osii#source_item'] ?? null;
        $sourceSitePropertyId = $localProperties['http://omeka.org/s/vocabs/o-module-osii#source_site'] ?? null;

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
            $this->logItemIds($osiiItemIdsChunk);
            $query->setParameter('osiiItemIds', $osiiItemIdsChunk);
            foreach ($query->toIterable() as $osiiItemEntity) {
                $localItemEntity = $osiiItemEntity->getLocalItem();
                $remoteItem = $osiiItemEntity->getSnapshotItem();
                $localItem = [];
                $localItem = $this->mapOwner($localItem, $remoteItem);
                $localItem = $this->mapVisibility($localItem, $remoteItem);
                $localItem = $this->mapClass($localItem, $remoteItem);
                $localItem = $this->mapValues($localItem, $remoteItem);
                // Set the item set. Preserve any existing item set associations.
                foreach ($localItemEntity->getItemSets()->getKeys() as $itemSetId) {
                    $localItem['o:item_set'][] = ['o:id' => $itemSetId];
                }
                $localItemSet = $this->getImportEntity()->getLocalItemSet();
                if ($localItemSet) {
                    $localItem['o:item_set'][] = ['o:id' => $localItemSet->getId()];
                }
                // Add the source item value.
                if ($sourceItemPropertyId && $this->getImportEntity()->getAddSourceItem()) {
                    $localItem[$sourceItemPropertyId][] = [
                        'type' => 'uri',
                        'property_id' => $sourceItemPropertyId,
                        '@id' => sprintf(
                            '%s/items/%s',
                            $this->getImportEntity()->getRootEndpoint(),
                            $osiiItemEntity->getRemoteItemId()
                        ),
                    ];
                }
                // Add the source site value.
                if ($sourceSitePropertyId && $this->getImportEntity()->getSourceSite()) {
                    $localItem[$sourceSitePropertyId][] = [
                        'type' => 'uri',
                        'property_id' => $sourceSitePropertyId,
                        '@id' => $this->getImportEntity()->getSourceSite(),
                    ];
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

        // Import media from their snapshot.
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
            $this->logMediaIds($osiiMediaIdsChunk);
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
                $localMedia = $this->mapValues($localMedia, $remoteMedia);
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
        if (isset($remoteResource['o:resource_class']) && isset($this->classMap[$remoteResource['o:resource_class']['o:id']])) {
            $localResource['o:resource_class']['o:id'] = $this->classMap[$remoteResource['o:resource_class']['o:id']];
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
            if (!isset($this->dataTypeMap[$remoteValue['type']])) {
                // Data type is not on local installation. Ignore value.
                continue;
            }
            if (!isset($this->propertyMap[$remoteValue['property_id']])) {
                // Property is not on local installation. Ignore value.
                continue;
            }
            if (isset($remoteValue['value_resource_id'])) {
                if (!isset($this->itemMap[$remoteValue['value_resource_id']])) {
                    // Item is not on local installation. Ignore value.
                    continue;
                }
                $remoteValue['value_resource_id'] = $this->itemMap[$remoteValue['value_resource_id']];
            }
            $dataType = $this->dataTypeMap[$remoteValue['type']];
            $propertyId = $this->propertyMap[$remoteValue['property_id']];
            $remoteValue['type'] = $dataType;
            $remoteValue['property_id'] = $propertyId;
            if (!isset($localResource[$propertyId])) {
                $localResource[$propertyId] = [];
            }
            $localResource[$propertyId][] = $remoteValue;
        }
        return $localResource;
    }

    /**
     * Log OSII item IDs.
     *
     * @param array $ids
     */
    protected function logItemIds(array $ids)
    {
        $osiiIds = '';
        foreach (array_chunk($ids, 10) as $idsChunk) {
            $osiiIds .= "\n\t";
            foreach ($idsChunk as $id) {
                $osiiIds .= $id . ', ';
            }
        }
        $this->getLogger()->info(sprintf('Iterating OSII items:%s', $osiiIds));
    }

    /**
     * Log OSII media IDs.
     *
     * @param array $ids
     */
    protected function logMediaIds(array $ids)
    {
        $osiiIds = '';
        foreach (array_chunk($ids, 10) as $idsChunk) {
            $osiiIds .= "\n\t";
            foreach ($idsChunk as $id) {
                $osiiIds .= $id . ', ';
            }
        }
        $this->getLogger()->info(sprintf('Iterating OSII media:%s', $osiiIds));
    }
}
