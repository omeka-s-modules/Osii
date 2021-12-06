<?php
namespace Osii\MediaIngesterMapper;

class Html implements MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'html';
        $localResource['html'] = $remoteResource['data']['html'];
        return $localResource;
    }
}
