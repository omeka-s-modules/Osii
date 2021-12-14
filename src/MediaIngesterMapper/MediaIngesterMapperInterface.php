<?php
namespace Osii\MediaIngesterMapper;

interface MediaIngesterMapperInterface
{
    /**
     * Map for create operation.
     *
     * Must set o:ingester and whatever values are needed to create the media.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array
     */
    public function mapForCreate(array $localResource, array $remoteResource) : array;

    /**
     * Map for update operation.
     *
     * For ingesters that implement MutableIngesterInterface, set whatever
     * values are needed to update the media.
     *
     * @param array $localResource
     * @param array $remoteResource
     * @return array
     */
    public function mapForUpdate(array $localResource, array $remoteResource) : array;
}
