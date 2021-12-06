<?php
namespace Osii\MediaIngesterMapper;

class Youtube implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'youtube';
        return $localResource;
    }
}
