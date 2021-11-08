<?php
namespace Osii\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Job;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class OsiiImport extends AbstractEntity
{
    /**
     * @Id
     * @Column(
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $owner;

    public function setOwner(?User $owner = null) : void
    {
        $this->owner = $owner;
    }

    public function getOwner() : ?User
    {
        return $this->owner;
    }

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\ItemSet"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $localItemSet;

    public function setLocalItemSet(?ItemSet $localItemSet = null) : void
    {
        $this->localItemSet = $localItemSet;
    }

    public function getLocalItemSet() : ?ItemSet
    {
        return $this->localItemSet;
    }

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Job"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $snapshotJob;

    public function setSnapshotJob(?Job $snapshotJob = null) : void
    {
        $this->snapshotJob = $snapshotJob;
    }

    public function getSnapshotJob() : ?Job
    {
        return $this->snapshotJob;
    }

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Job"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $importJob;

    public function setImportJob(?Job $importJob = null) : void
    {
        $this->importJob = $importJob;
    }

    public function getImportJob() : ?Job
    {
        return $this->importJob;
    }

    /**
     * @Column(
     *     type="string",
     *     length=255,
     *     nullable=false
     * )
     */
    protected $label;

    public function setLabel(string $label) : void
    {
        $this->label = $label;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    /**
     * @Column(
     *     type="text",
     *     nullable=false
     * )
     */
    protected $rootEndpoint;

    public function setRootEndpoint(string $rootEndpoint) : void
    {
        $this->rootEndpoint = $rootEndpoint;
    }

    public function getRootEndpoint() : string
    {
        return $this->rootEndpoint;
    }

    /**
     * @Column(
     *     type="string",
     *     length=32,
     *     nullable=true
     * )
     */
    protected $keyIdentity;

    public function setKeyIdentity(?string $keyIdentity) : void
    {
        $this->keyIdentity = $keyIdentity;
    }

    public function getKeyIdentity() : ?string
    {
        return $this->keyIdentity;
    }

    /**
     * @Column(
     *     type="string",
     *     length=32,
     *     nullable=true
     * )
     */
    protected $keyCredential;

    public function setKeyCredential(?string $keyCredential) : void
    {
        $this->keyCredential = $keyCredential;
    }

    public function getKeyCredential() : ?string
    {
        return $this->keyCredential;
    }

    /**
     * @Column(
     *     type="text",
     *     nullable=true
     * )
     */
    protected $remoteQuery;

    public function setRemoteQuery(?string $remoteQuery) : void
    {
        $this->remoteQuery = $remoteQuery;
    }

    public function getRemoteQuery() : ?string
    {
        return $this->remoteQuery;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotDataTypes;

    public function setSnapshotDataTypes(?string $snapshotDataTypes) : void
    {
        $this->snapshotDataTypes = $snapshotDataTypes;
    }

    public function getSnapshotDataTypes() : ?string
    {
        return $this->snapshotDataTypes;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotProperties;

    public function setSnapshotProperties(?string $snapshotProperties) : void
    {
        $this->snapshotProperties = $snapshotProperties;
    }

    public function getSnapshotProperties() : ?string
    {
        return $this->snapshotProperties;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotClasses;

    public function setSnapshotClasses(?string $snapshotClasses) : void
    {
        $this->snapshotClasses = $snapshotClasses;
    }

    public function getSnapshotClasses() : ?string
    {
        return $this->snapshotClasses;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $dataTypeMap;

    public function setDataTypeMap(?string $dataTypeMap) : void
    {
        $this->dataTypeMap = $dataTypeMap;
    }

    public function getDataTypeMap() : ?string
    {
        return $this->dataTypeMap;
    }

    /**
     * @Column(
     *     type="datetime",
     *     nullable=false
     * )
     */
    protected $created;

    public function setCreated(DateTime $created) : void
    {
        $this->created = $created;
    }

    public function getCreated() : DateTime
    {
        return $this->created;
    }

    /**
     * @Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $modified;

    public function setModified(?DateTime $modified) : void
    {
        $this->modified = $modified;
    }

    public function getModified() : ?DateTime
    {
        return $this->modified;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs) : void
    {
        $this->setCreated(new DateTime('now'));
    }
}
