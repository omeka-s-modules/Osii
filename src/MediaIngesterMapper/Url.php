<?php
namespace Osii\MediaIngesterMapper;

class Url implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'url';
        $localResource['ingest_url'] = $remoteResource['o:original_url'];
        $localResource['o:source'] = $remoteResource['o:source'];
        return $localResource;
    }
}
