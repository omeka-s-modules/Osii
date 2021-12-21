<?php
namespace Osii\ResourceMapper;

use Omeka\Job\JobInterface;

abstract class AbstractResourceMapper implements ResourceMapperInterface
{
    protected $job;

    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }
}
