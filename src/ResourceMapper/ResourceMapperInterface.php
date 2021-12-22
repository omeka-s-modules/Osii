<?php
namespace Osii\ResourceMapper;

interface ResourceMapperInterface
{
    /**
     * Prepare resource data for the snapshot.
     *
     * Use this method in the event that the remote resource JSON-LD needs
     * modification before its snapshot is saved.
     *
     * @param array $remoteResource The remote resource JSON-LD
     * @return array The remote resource JSON-LD
     */
    public function prepareResource(array $remoteResource) : array;

    /**
     * Map remote to local resource data for import.
     *
     * Use this method to map remote data to local data before the resource is
     * imported.
     *
     * @param array $localResource The local resource JSON-LD
     * @param array $remoteResource The remote resource JSON-LD
     * @return array The local resource JSON-LD
     */
    public function mapResource(array $localResource, array $remoteResource) : array;
}
