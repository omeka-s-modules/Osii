<?php
namespace Osii\ResourceMapper;

class ResourceClass extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $mappings = $this->getJob()->getMappings();
        if (isset($remoteResource['o:resource_class'])
            && $mappings->get('classes', $remoteResource['o:resource_class']['o:id'])
        ) {
            $localResource['o:resource_class']['o:id'] = $mappings->get('classes', $remoteResource['o:resource_class']['o:id']);
        }
        return $localResource;
    }
}
