<?php
namespace Osii\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OsiiImportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-osii:Import';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();
        $localItemSet = $this->localItemSet();
        $modified = $this->modified();
        return [
            'o:owner' => $owner ? $owner->getReference() : null,
            'o-module-osii:local_item_set' => $localItemSet ? $localItemSet->getReference() : null,
            'o:label' => $this->label(),
            'o-module-osii:root_endpoint' => $this->rootEndpoint(),
            'o-module-osii:key_identity' => $this->keyIdentity(),
            'o-module-osii:key_credential' => $this->keyCredential(),
            'o-module-osii:remote_query' => $this->remoteQuery(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/osii-import-id',
            [
                'controller' => 'import',
                'action' => $action,
                'import-id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function owner()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getOwner());
    }

    public function localItemSet()
    {
        return $this->getAdapter('item_sets')->getRepresentation($this->resource->getLocalItemSet());
    }

    public function snapshotJob()
    {
        return $this->getAdapter('jobs')->getRepresentation($this->resource->getSnapshotJob());
    }

    public function importJob()
    {
        return $this->getAdapter('jobs')->getRepresentation($this->resource->getImportJob());
    }

    public function label()
    {
        return $this->resource->getLabel();
    }

    public function rootEndpoint()
    {
        return $this->resource->getRootEndpoint();
    }

    public function keyIdentity()
    {
        return $this->resource->getKeyIdentity();
    }

    public function keyCredential()
    {
        return $this->resource->getKeyCredential();
    }

    public function remoteQuery()
    {
        return $this->resource->getRemoteQuery();
    }

    public function snapshotDataTypes()
    {
        return $this->resource->getSnapshotDataTypes();
    }

    public function snapshotProperties()
    {
        return $this->resource->getSnapshotProperties();
    }

    public function snapshotClasses()
    {
        return $this->resource->getSnapshotClasses();
    }

    public function dataTypeMap()
    {
        return $this->resource->getDataTypeMap();
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
