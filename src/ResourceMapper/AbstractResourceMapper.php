<?php
namespace Osii\ResourceMapper;

use Omeka\Job\JobInterface;

abstract class AbstractResourceMapper implements ResourceMapperInterface
{
    protected $job;

    /**
     * @param JobInterface $job
     */
    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * @return JobInterface
     */
    public function getJob() : JobInterface
    {
        return $this->job;
    }

    /**
     * @param array $remoteResource
     * @return array
     */
    public function prepareResource(array $remoteResource) : array
    {
        return $remoteResource;
    }
}
