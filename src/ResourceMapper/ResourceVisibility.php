<?php
namespace Osii\ResourceMapper;

class ResourceVisibility extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $localResource['o:is_public'] = $remoteResource['o:is_public'];
        return $localResource;
    }
}
