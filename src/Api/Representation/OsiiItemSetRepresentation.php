<?php
namespace Osii\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OsiiItemSetRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-osii:ItemSet';
    }

    public function getJsonLd()
    {
        $localItemSet = $this->localItemSet();
        $modified = $this->modified();
        return [
            'o-module-osii:import' => $this->import()->getReference(),
            'o-module-osii:local_item_set' => $localItemSet ? $localItemSet->getReference() : null,
            'o-module-osii:remote_item_set_id' => $this->remoteItemSetId(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function import()
    {
        return $this->getAdapter('osii_imports')->getRepresentation($this->resource->getImport());
    }

    public function localItemSet()
    {
        return $this->getAdapter('item_sets')->getRepresentation($this->resource->getLocalItemSet());
    }

    public function snapshotItemSet()
    {
        return $this->resource->getSnapshotItemSet();
    }

    public function remoteItemSetId()
    {
        return $this->resource->getRemoteItemSetId();
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
