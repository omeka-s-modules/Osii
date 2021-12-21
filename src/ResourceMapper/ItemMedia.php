<?php
namespace Osii\ResourceMapper;

class ItemMedia extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $resourceName = $this->getJob()->getResourceName($remoteResource);
        $mappings = $this->getJob()->getMappings();

        if ('items' !== $resourceName) {
            return $localResource;
        }

        // Map remote to local media. Media has already been imported in the
        // job, but this step is still necessary to save position and remove
        // media added locally since the last import.
        $localResource['o:media'] = [];
        foreach ($remoteResource['o:media'] as $remoteMedia) {
            $mediaId = $mappings->get('media', $remoteMedia['o:id']);
            if ($mediaId) {
                $localResource['o:media'][] = ['o:id' => $mediaId];
            }
        }

        return $localResource;
    }
}
