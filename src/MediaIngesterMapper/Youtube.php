<?php
namespace Osii\MediaIngesterMapper;

class Youtube extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'youtube';
        $localResource['o:source'] = $remoteResource['o:source'];
        $localResource['start'] = $remoteResource['data']['start'] ?? null;
        $localResource['end'] = $remoteResource['data']['end'] ?? null;
        return $localResource;
    }
}
