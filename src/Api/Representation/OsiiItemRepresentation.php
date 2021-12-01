<?php
namespace Osii\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OsiiItemRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-osii:Item';
    }

    public function getJsonLd()
    {
        $localItem = $this->localItem();
        $modified = $this->modified();
        return [
            'o-module-osii:import' => $this->import()->getReference(),
            'o-module-osii:local_item' => $localItem ? $localItem->getReference() : null,
            'o-module-osii:remote_item_id' => $this->remoteItemId(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function import()
    {
        return $this->getAdapter('osii_imports')->getRepresentation($this->resource->getImport());
    }

    public function localItem()
    {
        return $this->getAdapter('items')->getRepresentation($this->resource->getLocalItem());
    }

    public function snapshotItem()
    {
        return $this->resource->getSnapshotItem();
    }

    public function remoteItemId()
    {
        return $this->resource->getRemoteItemId();
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
