<?php
namespace Osii\MediaIngesterMapper;

class IiifPresentation extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'iiif_presentation';
        $localResource['o:source'] = $remoteResource['o:source'];
        return $localResource;
    }
}
