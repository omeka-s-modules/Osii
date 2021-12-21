<?php
namespace Osii\ResourceMapper;

interface ResourceMapperInterface
{
    /**
     * Map remote to local resource data.
     *
     * @param array $localResource The local resource JSON-LD
     * @param array $remoteResource The remote resource JSON-LD
     * @return array The local resource JSON-LD
     */
    public function mapResource(array $localResource, array $remoteResource) : array;
}
