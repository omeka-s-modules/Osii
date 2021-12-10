<?php
namespace Osii\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;
use Osii\Entity as OsiiEntity;

abstract class AbstractOsiiJob extends AbstractJob
{
    protected $apiManager;

    protected $entityManager;

    protected $importEntity;

    protected $logger;

    /**
     * Get the API manager.
     *
     * @return Omeka\Api\Manager
     */
    protected function getApiManager()
    {
        if (null === $this->apiManager) {
            // Set the API manager if not already set.
            $this->apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        }
        return $this->apiManager;
    }

    /**
     * Get the entity manager.
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            // Set the entity manager if not already set.
            $this->entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        }
        return $this->entityManager;
    }

    /**
     * Get the OSII import entity.
     *
     * @return Osii\Entity\OsiiEntity
     */
    protected function getImportEntity()
    {
        if (null === $this->importEntity) {
            // Set the entity if not already set.
            $this->importEntity = $this->getEntityManager()->find(
                OsiiEntity\OsiiImport::class,
                $this->getArg('import_id')
            );
        }
        // Preemptively merge the entity in the event that it was detached.
        $this->importEntity = $this->getEntityManager()->merge($this->importEntity);
        return $this->importEntity;
    }

    /**
     * Get the job logger.
     *
     * @return
     */
    protected function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        }
        return $this->logger;
    }

    /**
     * Get values from resource JSON-LD.
     *
     * @param array $resource
     * @return array
     */
    protected function getValuesFromResource($resource)
    {
        $resourceValues = [];
        foreach ($resource as $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                if (!is_array($value)) {
                    continue;
                }
                if (isset($value['type']) && isset($value['property_id'])) {
                    $resourceValues[] = $value;
                }
            }
        }
        return $resourceValues;
    }

    /**
     * Flush and clear the entity manager.
     *
     * Call this in iterations to save memory.
     */
    protected function flushClear()
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
        // Merge the Job entity as managed so logging works as expected.
        $this->getEntityManager()->merge($this->job);
    }
}
