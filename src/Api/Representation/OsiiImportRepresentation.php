<?php
namespace Osii\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Entity\Job;

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
            'o-module-osii:exclude_media' => $this->excludeMedia(),
            'o-module-osii:exclude_item_sets' => $this->excludeItemSets(),
            'o-module-osii:keep_removed_resources' => $this->keepRemovedResources(),
            'o-module-osii:add_source_resource' => $this->addSourceResource(),
            'o-module-osii:source_site' => $this->sourceSite(),
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

    public function snapshotItems()
    {
        return $this->resource->getSnapshotItems();
    }

    public function snapshotMedia()
    {
        return $this->resource->getSnapshotMedia();
    }

    public function snapshotItemSets()
    {
        return $this->resource->getSnapshotItemSets();
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

    public function snapshotVocabularies()
    {
        return $this->resource->getSnapshotVocabularies();
    }

    public function snapshotTemplates()
    {
        return $this->resource->getSnapshotTemplates();
    }

    public function snapshotMediaIngesters()
    {
        return $this->resource->getSnapshotMediaIngesters();
    }

    public function dataTypeMap()
    {
        return $this->resource->getDataTypeMap();
    }

    public function templateMap()
    {
        return $this->resource->getTemplateMap();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    public function snapshotCompleted()
    {
        return $this->resource->getSnapshotCompleted();
    }

    public function importCompleted()
    {
        return $this->resource->getImportCompleted();
    }

    public function excludeMedia()
    {
        return $this->resource->getExcludeMedia();
    }

    public function excludeItemSets()
    {
        return $this->resource->getExcludeItemSets();
    }

    public function keepRemovedResources()
    {
        return $this->resource->getKeepRemovedResources();
    }

    public function addSourceResource()
    {
        return $this->resource->getAddSourceResource();
    }

    public function sourceSite()
    {
        return $this->resource->getSourceSite();
    }

    public function localItemCount()
    {
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        return $apiManager->search('items', ['osii_import_id' => $this->id(), 'limit' => 0])->getTotalResults();
    }

    public function localMediaCount()
    {
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        return $apiManager->search('media', ['osii_import_id' => $this->id(), 'limit' => 0])->getTotalResults();
    }

    public function localItemSetCount()
    {
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        return $apiManager->search('item_sets', ['osii_import_id' => $this->id(), 'limit' => 0])->getTotalResults();
    }

    public function canDoSnapshot()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob
            ? in_array($snapshotJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]) : true;
        return $snapshotStatus && $importStatus;
    }

    public function canStopSnapshot()
    {
        $snapshotJob = $this->snapshotJob();
        $snapshotStatus = $snapshotJob
            ? in_array($snapshotJob->status(), [
                Job::STATUS_STARTING,
                Job::STATUS_IN_PROGRESS,
            ]
            ) : false;
        return $snapshotStatus;
    }

    public function canRefreshSnapshotStatus()
    {
        $snapshotJob = $this->snapshotJob();
        $snapshotStatus = $snapshotJob
            ? in_array($snapshotJob->status(), [
                Job::STATUS_STOPPING,
                Job::STATUS_STARTING,
                Job::STATUS_IN_PROGRESS,
            ]
            ) : false;
        return $snapshotStatus;
    }

    public function canViewSnapshotJob()
    {
        return (bool) $this->snapshotJob();
    }

    public function canPrepareImport()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : false;
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        return $snapshotStatus && $importStatus;
    }

    public function canDoImport()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : false;
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        return $snapshotStatus && $importStatus;
    }

    public function canStopImport()
    {
        $importJob = $this->importJob();
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_STARTING,
                Job::STATUS_IN_PROGRESS,
            ]
            ) : false;
        return $importStatus;
    }

    public function canEditImport()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob
            ? in_array($snapshotJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        return $snapshotStatus && $importStatus;
    }

    public function canRefreshImportStatus()
    {
        $importJob = $this->importJob();
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_STOPPING,
                Job::STATUS_STARTING,
                Job::STATUS_IN_PROGRESS,
            ]
            ) : false;
        return $importStatus;
    }

    public function canViewImportJob()
    {
        return (bool) $this->importJob();
    }

    public function canViewResources()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : false;
        $importStatus = $importJob
            ? in_array($importJob->status(), [
                Job::STATUS_COMPLETED,
                Job::STATUS_STOPPING,
                Job::STATUS_STOPPED,
                Job::STATUS_ERROR,
            ]
            ) : true;
        return $snapshotStatus && $importStatus;
    }

    public function snapshotStatus()
    {
        $snapshotJob = $this->snapshotJob();
        return $snapshotJob ? $snapshotJob->status() : 'no_snapshot';
    }

    public function importStatus()
    {
        $importJob = $this->importJob();
        return $importJob ? $importJob->status() : 'no_import';
    }

    public function snapshotStatusLabel()
    {
        return 'no_snapshot' === $this->snapshotStatus()
            ? 'Not taken' // @translate
            : $this->snapshotJob()->statusLabel();
    }

    public function importStatusLabel()
    {
        return 'no_import' === $this->importStatus()
            ? 'Not imported' // @translate
            : $this->importJob()->statusLabel();
    }
}
