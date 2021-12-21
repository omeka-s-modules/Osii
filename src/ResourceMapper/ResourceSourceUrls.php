<?php
namespace Osii\ResourceMapper;

class ResourceSourceUrls extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $mappings = $this->getJob()->getMappings();
        $importEntity = $this->getJob()->getImportEntity();
        $resourceName = $this->getJob()->getResourceName($remoteResource);

        // Add the source resource value.
        $sourceResourcePropertyId = $mappings->get(
            'localProperties',
            'http://omeka.org/s/vocabs/o-module-osii#source_resource'
        );
        if ($sourceResourcePropertyId && $importEntity->getAddSourceResource()) {
            $localResource[$sourceResourcePropertyId][] = [
                'type' => 'uri',
                'property_id' => $sourceResourcePropertyId,
                '@id' => sprintf(
                    '%s/%s/%s',
                    $importEntity->getRootEndpoint(),
                    $resourceName,
                    $remoteResource['o:id']
                ),
            ];
        }
        // Add the source site value.
        $sourceSitePropertyId = $mappings->get(
            'localProperties',
            'http://omeka.org/s/vocabs/o-module-osii#source_site'
        );
        if ($sourceSitePropertyId && $importEntity->getSourceSite()) {
            $localResource[$sourceSitePropertyId][] = [
                'type' => 'uri',
                'property_id' => $sourceSitePropertyId,
                '@id' => $importEntity->getSourceSite(),
            ];
        }
        return $localResource;
    }
}
