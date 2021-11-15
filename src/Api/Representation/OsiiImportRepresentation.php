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

    public function snapshotVocabularies()
    {
        return $this->resource->getSnapshotVocabularies();
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

    public function snapshotCompleted()
    {
        return $this->resource->getSnapshotCompleted();
    }

    public function importCompleted()
    {
        return $this->resource->getImportCompleted();
    }

    /**
     * Can the user take a snapshot?
     *
     * @return bool
     */
    public function canDoSnapshot()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : true;
        $importStatus = $importJob ? Job::STATUS_COMPLETED === $importJob->status() : true;
        return $snapshotStatus && $importStatus;
    }

    /**
     * Can the user stop a snapshot?
     *
     * @return bool
     */
    public function canStopSnapshot()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED !== $snapshotJob->status() : false;
        $importStatus = $importJob ? Job::STATUS_COMPLETED === $importJob->status() : true;
        return $snapshotStatus && $importStatus;
    }

    /**
     * Can the user prepare import?
     *
     * @return bool
     */
    public function canPrepareImport()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : $this->snapshotCompleted();
        $importStatus = $importJob ? Job::STATUS_COMPLETED === $importJob->status() : true;
        return $snapshotStatus && $importStatus;
    }

    /**
     * Can the user import?
     *
     * @return bool
     */
    public function canDoImport()
    {
        $snapshotJob = $this->snapshotJob();
        $importJob = $this->importJob();
        $snapshotStatus = $snapshotJob ? Job::STATUS_COMPLETED === $snapshotJob->status() : $this->snapshotCompleted();
        $importStatus = $importJob ? Job::STATUS_COMPLETED === $importJob->status() : true;
        return $snapshotStatus && $importStatus && null !== $this->dataTypeMap();
    }

    public function snapshotStatus()
    {
        $snapshotJob = $this->snapshotJob();
        $snapshotCompleted = $this->snapshotCompleted();
        if ($snapshotJob) {
            return $snapshotJob->status();
        } elseif ($snapshotCompleted) {
            $status = Job::STATUS_STOPPED;
        } else {
            $status = 'no_snapshot';
        }
        return $status;
    }

    public function snapshotStatusLabel()
    {
        $snapshotJob = $this->snapshotJob();
        $snapshotStatus = $this->snapshotStatus();
        switch ($snapshotStatus) {
            case 'no_snapshot':
                $label = 'Not taken'; // @translate
                break;
            case Job::STATUS_STARTING:
                $label = 'Starting'; // @translate
                break;
            case Job::STATUS_STOPPING:
                $label = 'Stopping'; // @translate
                break;
            case Job::STATUS_IN_PROGRESS:
                $label = 'In progress'; // @translate
                break;
            case Job::STATUS_COMPLETED:
                $label = 'Completed'; // @translate
                break;
            case Job::STATUS_STOPPED:
                $label = 'Stopped'; // @translate
                break;
            case Job::STATUS_ERROR:
                $label = 'Error'; // @translate
                break;
            default:
                $label = 'Unknown'; // @translate
        }
        return $label;
    }
}
