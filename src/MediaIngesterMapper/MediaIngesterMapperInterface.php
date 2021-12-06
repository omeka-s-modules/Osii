<?php
namespace Osii\MediaIngesterMapper;

interface MediaIngesterMapperInterface
{
    public function mapIngester(array $localResource, array $remoteResource) : array;
}
