<?php
namespace Osii\Job;

use DateTime;
use Omeka\Job\AbstractJob;
use Osii\Entity as OsiiEntity;

class DoImport extends AbstractJob
{
    protected $importEntity;

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
    }
}
