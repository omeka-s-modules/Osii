<?php
namespace Osii\MediaIngesterMapper;

class Oembed implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'oembed';
        $localResource['o:source'] = $remoteResource['o:source'];
        return $localResource;
    }
}
