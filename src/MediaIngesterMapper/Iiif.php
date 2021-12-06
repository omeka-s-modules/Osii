<?php
namespace Osii\MediaIngesterMapper;

class Iiif implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'iiif';
        return $localResource;
    }
}
