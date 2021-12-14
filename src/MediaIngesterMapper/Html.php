<?php
namespace Osii\MediaIngesterMapper;

class Html extends AbstractMediaIngesterMapper
{
    public function mapForCreate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:ingester'] = 'html';
        $localResource['html'] = $remoteResource['data']['html'];
        return $localResource;
    }

    public function mapForUpdate(array $localResource, array $remoteResource) : array
    {
        $localResource['o:media'] = [
            '__index__' => [
                'html' => $remoteResource['data']['html'],
            ],
        ];
        return $localResource;
    }
}
