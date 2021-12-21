<?php
namespace Osii\ResourceMapper;

class Owner extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $localResource['o:owner']['o:id'] = $this->getJob()->getJobEntity()->getOwner()->getId();
        return $localResource;
    }
}
