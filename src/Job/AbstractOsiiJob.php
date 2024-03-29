<?php
namespace Osii\Job;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Entity\Job as JobEntity;
use Omeka\Job\AbstractJob;
use Osii\Entity as OsiiEntity;

abstract class AbstractOsiiJob extends AbstractJob
{
    protected $apiManager;

    protected $entityManager;

    protected $importEntity;

    protected $logger;

    protected $originalIdentityMap;

    public function __construct(JobEntity $job, ServiceLocatorInterface $serviceLocator)
    {
        parent::__construct($job, $serviceLocator);
        // Set the original identity map so we have a snapshot of the original
        // state of the entity manager.
        $this->originalIdentityMap = $this->getEntityManager()->getUnitOfWork()->getIdentityMap();
    }

    /**
     * Get the API manager.
     *
     * @return Omeka\Api\Manager
     */
    public function getApiManager()
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
    public function getEntityManager()
    {
        if (null === $this->entityManager) {
            // Set the entity manager if not already set.
            $this->entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        }
        return $this->entityManager;
    }

    /**
     * Get the job logger.
     *
     * @return
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            // Set the logger if not already set.
            $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        }
        return $this->logger;
    }

    /**
     * Get the job entity.
     *
     * @return Omeka\Entity\Job
     */
    public function getJobEntity()
    {
        return $this->job;
    }

    /**
     * Get the OSII import entity.
     *
     * @return Osii\Entity\OsiiEntity
     */
    public function getImportEntity()
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
     * Get values from resource JSON-LD.
     *
     * @param array $resource
     * @return array
     */
    public function getValuesFromResource(array $resource)
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
     * Get value annotations from value JSON-LD.
     *
     * @param array $value
     * @return array
     */
    public function getValueAnnotationsFromValue(array $value)
    {
        $valueAnnotations = [];
        if (isset($value['@annotation']) && is_array($value['@annotation'])) {
            $valueAnnotations = $this->getValuesFromResource($value['@annotation']);
        }
        return $valueAnnotations;
    }

    /**
     * Get the resource name from resource JSON-LD.
     *
     * @param array $resource
     * @return string
     */
    public function getResourceName(array $resource)
    {
        $type = $resource['@type'];
        if ('o:Item' === $type || (is_array($type) && in_array('o:Item', $type))) {
            return 'items';
        }
        if ('o:Media' === $type || (is_array($type) && in_array('o:Media', $type))) {
            return 'media';
        }
        if ('o:ItemSet' === $type || (is_array($type) && in_array('o:ItemSet', $type))) {
            return 'item_sets';
        }
    }

    /**
     * Log IDs from and array of JSON-LD resources or an array of integers.
     *
     * @param array $ids
     * @param string $message
     */
    public function logIds(array $ids, $message)
    {
        $idsLog = '';
        foreach (array_chunk($ids, 10) as $idsChunk) {
            $idsLog .= "\n\t";
            foreach ($idsChunk as $id) {
                $idsLog .= (is_array($id) ? $id['o:id'] : $id) . ', ';
            }
        }
        $this->getLogger()->info(sprintf('%s:%s', $message, $idsLog));
    }

    /**
     * Flush and clear the entity manager.
     *
     * Call this in iterations to save memory.
     */
    public function flushClear()
    {
        $entityManager = $this->getEntityManager();

        // Flush the entity manager to persist changes.
        $entityManager->flush();

        // Detach entities that were *not* part of the original state of the
        // entity manager to avoid reaching the memory limit. Do this instead of
        // explicitly clearing the entity manager to avoid the uncommon but
        // irksome "A new entity was found" Doctrine errors.
        $unitOfWork = $entityManager->getUnitOfWork();
        $identityMap = $unitOfWork->getIdentityMap();
        foreach ($identityMap as $entityClass => $entities) {
            foreach ($entities as $idHash => $entity) {
                if (!isset($this->originalIdentityMap[$entityClass][$idHash])) {
                    $entityManager->detach($entity);
                }
            }
        }
    }
}
