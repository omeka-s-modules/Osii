<?php
namespace Osii\ModuleMapper;

interface ModuleMapperInterface
{
    /**
     * Map remote to local module data.
     *
     * @param array $remoteResource The remote resource JSON-LD
     * @param array $localResource The local resource JSON-LD
     * @return array The local resource JSON-LD
     */
    public function mapModule(array $remoteResource, array $localResource) : array;
}
