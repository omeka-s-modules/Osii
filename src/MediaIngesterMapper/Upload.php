<?php
namespace Osii\MediaIngesterMapper;

class Upload extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'url';
        $localResource['o:source'] = $remoteResource['o:source'];
        $localResource['ingest_url'] = $this->getIngestUrl($remoteResource['o:original_url']);
        return $localResource;
    }
}
