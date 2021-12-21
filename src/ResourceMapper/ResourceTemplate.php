<?php
namespace Osii\ResourceMapper;

class ResourceTemplate extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $mappings = $this->getJob()->getMappings();
        if (isset($remoteResource['o:resource_template'])
            && $mappings->get('templates', $remoteResource['o:resource_template']['o:id'])
        ) {
            $localResource['o:resource_template']['o:id'] = $mappings->get('templates', $remoteResource['o:resource_template']['o:id']);
        }
        return $localResource;
    }
}
