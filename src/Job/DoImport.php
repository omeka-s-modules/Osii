<?php
namespace Osii\Job;

use DateTime;
use Omeka\Entity as OmekaEntity;

class DoImport extends AbstractOsiiJob
{
    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        // Remote items may have been deleted (or simply removed) since the
        // previous snapshot. These must be deleted locally so that the local
        // items are in sync with the remote ones. Here we get these removed
        // items and delete them. Note that not all OSII items will have related
        // Omeka items becuase snapshots can change without a subsequent import.
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
        $this->getApiManager()->batchDelete('items', $localItemsToDelete);

        // Remote items may have been created since the previous snapshot. These
        // must be created locally. Here we create these new items and assign
        // them to their related OSII items.
        $dql = 'SELECT i
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import
        AND i.localItem IS NULL';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('import', $this->getImportEntity());
        $i = 1;
        $batchSize = 50;
        foreach ($query->toIterable() as $osiiItemEntity) {
            $localItem = new OmekaEntity\Item;
            $localItem->setCreated(new DateTime('now'));
            $this->getEntityManager()->persist($localItem);
            $osiiItemEntity->setLocalItem($localItem);
            if (0 === ($i % $batchSize)) {
                $this->getEntityManager()->flush();
                $this->getEntityManager()->clear();
            }
            $i++;
        }
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // Get the remote/local ID maps. Keys are remote IDs; values are local
        // IDs. Here we're mapping the remote items, properties, and classes to
        // the local items, properties, and classes.
        $dql = 'SELECT i.remoteItemId AS remote_item, IDENTITY(i.localItem) AS local_item
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('import', $this->getImportEntity());
        $itemMap = array_column($query->getResult(), 'local_item', 'remote_item');

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

        $propertyMap = [];
        foreach ($snapshotProperties as $remotePropertyId => $remoteProperty) {
            $namespaceUri = $snapshotVocabularies[$remoteProperty['vocabulary_id']]['namespace_uri'];
            $localName = $remoteProperty['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            if (isset($localProperties[$uri])) {
                $propertyMap[$remotePropertyId] = $localProperties[$uri];
            }
        }

        $classMap = [];
        foreach ($snapshotClasses as $remoteClassId => $remoteClass) {
            $namespaceUri = $snapshotVocabularies[$remoteClass['vocabulary_id']]['namespace_uri'];
            $localName = $remoteClass['local_name'];
            $uri = sprintf('%s%s', $namespaceUri, $localName);
            if (isset($localClasses[$uri])) {
                $classMap[$remoteClassId] = $localClasses[$uri];
            }
        }

        $dataTypeMap = $this->getImportEntity()->getDataTypeMap();

        // Import items from their snapshot.
        $dql = 'SELECT i
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('import', $this->getImportEntity());
        $i = 1;
        $batchSize = 50;
        foreach ($query->toIterable() as $osiiItemEntity) {
            $localItemEntity = $osiiItemEntity->getLocalItem();
            $remoteItem = $osiiItemEntity->getSnapshotItem();
            $localItem = [];

            // Set the owner.
            $localItem['o:owner']['o:id'] = $this->job->getOwner()->getId();
            // Set the visibility.
            $localItem['o:is_public'] = $remoteItem['o:is_public'];
            // Set the item set. Preserve any existing item set associations.
            foreach ($localItemEntity->getItemSets()->getKeys() as $itemSetId) {
                $localItem['o:item_set'][] = ['o:id' => $itemSetId];
            }
            $localItemSet = $this->getImportEntity()->getLocalItemSet();
            if ($localItemSet) {
                $localItem['o:item_set'][] = ['o:id' => $localItemSet->getId()];
            }
            // Set the class.
            if (isset($remoteItem['o:resource_class']) && isset($classMap[$remoteItem['o:resource_class']['o:id']])) {
                $localItem['o:resource_class']['o:id'] = $classMap[$remoteItem['o:resource_class']['o:id']];
            }
            // Set the values.
            foreach ($this->getValuesFromResource($remoteItem) as $remoteValue) {
                if (!isset($dataTypeMap[$remoteValue['type']])) {
                    // Data type is not on local installation. Ignore value.
                    continue;
                }
                if (!isset($propertyMap[$remoteValue['property_id']])) {
                    // Property is not on local installation. Ignore value.
                    continue;
                }
                if (isset($remoteValue['value_resource_id'])) {
                    if (!isset($itemMap[$remoteValue['value_resource_id']])) {
                        // Item is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $itemMap[$remoteValue['value_resource_id']];
                }
                $dataType = $dataTypeMap[$remoteValue['type']];
                $propertyId = $propertyMap[$remoteValue['property_id']];
                $remoteValue['type'] = $dataType;
                $remoteValue['property_id'] = $propertyId;
                if (!isset($localItem[$propertyId])) {
                    $localItem[$propertyId] = [];
                }
                $localItem[$propertyId][] = $remoteValue;
            }
            $updateOptions = [
                'flushEntityManager' => false, // Flush (and clear) only once per batch.
                'responseContent' => 'resource', // Avoid the overhead of composing the representation.
            ];
            $this->getApiManager()->update('items', $localItemEntity->getId(), $localItem, [], $updateOptions);
            if (0 === ($i % $batchSize)) {
                if ($this->shouldStop()) {
                    return;
                }
                $this->getEntityManager()->flush();
                $this->getEntityManager()->clear();
            }
            $i++;
        }
        $this->getImportEntity()->setImportCompleted(new DateTime('now'));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }
}
