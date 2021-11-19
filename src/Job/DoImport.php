<?php
namespace Osii\Job;

use DateTime;
use Omeka\Entity as OmekaEntity;
use Omeka\Job\AbstractJob;
use Osii\Entity as OsiiEntity;

class DoImport extends AbstractJob
{
    /**
     * Sync a dataset with its item set.
     */
    public function perform()
    {
        ini_set('memory_limit', '500M'); // Set a high memory limit.

        $importId = $this->getArg('import_id');

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Set the import entity.
        $importEntity = $entityManager->find(OsiiEntity\OsiiImport::class, $importId);

        // Remote items may have been deleted or removed since the previous
        // snapshot. These must be removed locally so that the local items are
        // in sync with the remote ones. Here we get these removed items and
        // delete them. Note that not all OSII items will have related Omeka
        // items becuase snapshots can change without a subsequent import.
        $dql = 'SELECT i.id AS osii_item, IDENTITY(i.localItem) AS local_item
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import
        AND i.remoteItemId NOT IN (:snapshotItems)';
        $query = $entityManager->createQuery($dql)
            ->setParameters([
                'import' => $importEntity,
                'snapshotItems' => $importEntity->getSnapshotItems(),
            ]);
        $itemsToDelete = $query->getResult();
        $osiiItemsToDelete = array_filter(array_column($itemsToDelete, 'osii_item'));
        $localItemsToDelete = array_filter(array_column($itemsToDelete, 'local_item'));
        $apiManager->batchDelete('osii_items', $osiiItemsToDelete);
        $apiManager->batchDelete('items', $localItemsToDelete);

        // Remote items may have been created since the previous snapshot. These
        // must be created locally. Here we create these new items and assign
        // them to their related OSII items.
        $dql = 'SELECT i
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import
        AND i.localItem IS NULL';
        $query = $entityManager->createQuery($dql)
            ->setParameter('import', $importEntity);
        $i = 1;
        $batchSize = 50;
        foreach ($query->toIterable() as $osiiItem) {
            $localItem = new OmekaEntity\Item;
            $localItem->setCreated(new DateTime('now'));
            $entityManager->persist($localItem);
            $osiiItem->setLocalItem($localItem);
            if (0 === ($i % $batchSize)) {
                $entityManager->flush();
                $entityManager->clear();
            }
            $i++;
        }
        $entityManager->flush();
        $entityManager->clear();

        // @todo: cache the `remote_item_id` => `local_item_id` map and the
        // local vocabulary data (properties and classes).

        /*
        Then, for each row in `osii_item`:
            - Extract the values in `snapshot_item` and transform them as
            necessary, using the `osii_import`.`data_type_map` (for type), the
            remote/local item ID map (for value_resource_id), and the local
            vocabulary data (for property_id).
            - Extract the resource class in `snapshot_item` and transform it
            using the local vocabulary data (for o:id).
            - Set the item set o:id to the one configured in `osii_import`
            .`local_item_set_id`
            - Add remote item's API URL to dcterms:source (URI type).
            - Add o:is_public
            - Add o:title ???
            - Use the API manager to update the existing Omeka item using the
            transformed JSON-LD.

        */
    }
}
