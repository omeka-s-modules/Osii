<?php
namespace Osii\MediaIngesterMapper;

class Iiif extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'iiif';
        $localResource['o:source'] = $remoteResource['o:source'];
        return $localResource;
    }
}
