<?php
namespace Osii\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OsiiMediaRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-osii:Media';
    }

    public function getJsonLd()
    {
        $localMedia = $this->localMedia();
        $modified = $this->modified();
        return [
            'o-module-osii:osii_item' => $this->osiiItem()->getReference(),
            'o-module-osii:local_media' => $localMedia ? $localMedia->getReference() : null,
            'o-module-osii:remote_media_id' => $this->remoteMediaId(),
            'o-module-osii:position' => $this->position(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function osiiItem()
    {
        return $this->getAdapter('osii_items')->getRepresentation($this->resource->getOsiiItem());
    }

    public function localMedia()
    {
        return $this->getAdapter('media')->getRepresentation($this->resource->getLocalMedia());
    }

    public function snapshotMedia()
    {
        return $this->resource->getSnapshotMedia();
    }

    public function remoteMediaId()
    {
        return $this->resource->getRemoteMediaId();
    }

    public function position()
    {
        return $this->resource->getPosition();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }
}
