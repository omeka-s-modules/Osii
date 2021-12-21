<?php
namespace Osii\ResourceMapper;

class ResourceOwner extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $localResource['o:owner']['o:id'] = $this->getJob()->getJobEntity()->getOwner()->getId();
        return $localResource;
    }
}
