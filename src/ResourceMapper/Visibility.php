<?php
namespace Osii\ResourceMapper;

class Visibility extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $localResource['o:is_public'] = $remoteResource['o:is_public'];
        return $localResource;
    }
}
