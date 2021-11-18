<?php
namespace Osii\Job;

use DateTime;
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

        // Items from a previous snapshot may have been removed before the
        // current snapshot. These must be deleted locally. Here we get these
        // removed items and delete them. Not all OSII items will have related
        // Omeka items becuase snapshots can change without a subsequent import.
        $dql = '
        SELECT i.id AS osii_item, IDENTITY(i.localItem) as local_item
        FROM Osii\Entity\OsiiItem i
        WHERE i.import = :import
        AND i.remoteItemId NOT IN (:snapshotItems)';
        $query = $entityManager->createQuery($dql)
            ->setParameters([
                'import' => $importEntity,
                'snapshotItems' => $importEntity->getSnapshotItems()
            ]);
        $itemsToDelete = $query->getResult();
        $query = $entityManager
            ->createQuery('DELETE Osii\Entity\OsiiItem i WHERE i.id IN (:osiiItems)')
            ->setParameter('osiiItems', array_column($itemsToDelete, 'osii_item'));
        $query->execute();
        $query = $entityManager
            ->createQuery('DELETE Omeka\Entity\Item i WHERE i.id IN (:localItems)')
            ->setParameter('localItems', array_column($itemsToDelete, 'local_item'));
        $query->execute();

        // @todo: create an item for every `osii_item` row that doesn't have a
        // `local_item_id`. Set that new item ID to `local_item_id`.

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
