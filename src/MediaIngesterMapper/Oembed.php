<?php
namespace Osii\MediaIngesterMapper;

class Oembed implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'oembed';
        return $localResource;
    }
}
