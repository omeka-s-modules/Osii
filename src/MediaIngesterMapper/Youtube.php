<?php
namespace Osii\MediaIngesterMapper;

class Youtube implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'youtube';
        $localResource['o:source'] = $remoteResource['o:source'];
        $localResource['start'] = $remoteResource['data']['start'];
        $localResource['end'] = $remoteResource['data']['end'];
        return $localResource;
    }
}
