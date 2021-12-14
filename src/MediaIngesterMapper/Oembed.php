<?php
namespace Osii\MediaIngesterMapper;

class Oembed extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'oembed';
        $localResource['o:source'] = $remoteResource['o:source'];
        return $localResource;
    }
}
