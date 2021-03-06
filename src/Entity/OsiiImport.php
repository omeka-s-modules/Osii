<?php
namespace Osii\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
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
    public function __construct()
    {
        $this->osiiItems = new ArrayCollection;
        $this->osiiMedia = new ArrayCollection;
    }

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
        $keyIdentity = trim($keyIdentity);
        $this->keyIdentity = $keyIdentity ?: null;
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
        $keyCredential = trim($keyCredential);
        $this->keyCredential = $keyCredential ?: null;
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
        $remoteQuery = trim($remoteQuery);
        $remoteQuery = ltrim($remoteQuery, '?');
        $this->remoteQuery = $remoteQuery ?: null;
    }

    public function getRemoteQuery() : ?string
    {
        return $this->remoteQuery;
    }

    /**
     * @Column(
     *     type="boolean",
     *     nullable=false
     * )
     */
    protected $excludeMedia = false;

    public function setExcludeMedia($excludeMedia) : void
    {
        $this->excludeMedia = (bool) $excludeMedia;
    }

    public function getExcludeMedia() : bool
    {
        return $this->excludeMedia;
    }

    /**
     * @Column(
     *     type="boolean",
     *     nullable=false
     * )
     */
    protected $excludeItemSets = false;

    public function setExcludeItemSets($excludeItemSets) : void
    {
        $this->excludeItemSets = (bool) $excludeItemSets;
    }

    public function getExcludeItemSets() : bool
    {
        return $this->excludeItemSets;
    }

    /**
     * @Column(
     *     type="boolean",
     *     nullable=false
     * )
     */
    protected $keepRemovedResources = false;

    public function setKeepRemovedResources($keepRemovedResources) : void
    {
        $this->keepRemovedResources = (bool) $keepRemovedResources;
    }

    public function getKeepRemovedResources() : bool
    {
        return $this->keepRemovedResources;
    }

    /**
     * @Column(
     *     type="boolean",
     *     nullable=false
     * )
     */
    protected $addSourceResource = false;

    public function setAddSourceResource($addSourceResource) : void
    {
        $this->addSourceResource = (bool) $addSourceResource;
    }

    public function getAddSourceResource() : bool
    {
        return $this->addSourceResource;
    }

    /**
     * @Column(
     *     type="text",
     *     nullable=true
     * )
     */
    protected $sourceSite;

    public function setSourceSite(string $sourceSite) : void
    {
        $sourceSite = trim($sourceSite);
        $this->sourceSite = $sourceSite ?: null;
    }

    public function getSourceSite() : ?string
    {
        return $this->sourceSite;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotItems;

    public function setSnapshotItems(?array $snapshotItems) : void
    {
        $this->snapshotItems = $snapshotItems;
    }

    public function getSnapshotItems() : ?array
    {
        return $this->snapshotItems;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotMedia;

    public function setSnapshotMedia(?array $snapshotMedia) : void
    {
        $this->snapshotMedia = $snapshotMedia;
    }

    public function getSnapshotMedia() : ?array
    {
        return $this->snapshotMedia;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotItemSets;

    public function setSnapshotItemSets(?array $snapshotItemSets) : void
    {
        $this->snapshotItemSets = $snapshotItemSets;
    }

    public function getSnapshotItemSets() : ?array
    {
        return $this->snapshotItemSets;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotDataTypes;

    public function setSnapshotDataTypes(?array $snapshotDataTypes) : void
    {
        $this->snapshotDataTypes = $snapshotDataTypes;
    }

    public function getSnapshotDataTypes() : ?array
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

    public function setSnapshotProperties(?array $snapshotProperties) : void
    {
        $this->snapshotProperties = $snapshotProperties;
    }

    public function getSnapshotProperties() : ?array
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

    public function setSnapshotClasses(?array $snapshotClasses) : void
    {
        $this->snapshotClasses = $snapshotClasses;
    }

    public function getSnapshotClasses() : ?array
    {
        return $this->snapshotClasses;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotVocabularies;

    public function setSnapshotVocabularies(?array $snapshotVocabularies) : void
    {
        $this->snapshotVocabularies = $snapshotVocabularies;
    }

    public function getSnapshotVocabularies() : ?array
    {
        return $this->snapshotVocabularies;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotTemplates;

    public function setSnapshotTemplates(?array $snapshotTemplates) : void
    {
        $this->snapshotTemplates = $snapshotTemplates;
    }

    public function getSnapshotTemplates() : ?array
    {
        return $this->snapshotTemplates;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotMediaIngesters;

    public function setSnapshotMediaIngesters(?array $snapshotMediaIngesters) : void
    {
        $this->snapshotMediaIngesters = $snapshotMediaIngesters;
    }

    public function getSnapshotMediaIngesters() : ?array
    {
        return $this->snapshotMediaIngesters;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $dataTypeMap;

    public function setDataTypeMap(?array $dataTypeMap) : void
    {
        $this->dataTypeMap = $dataTypeMap;
    }

    public function getDataTypeMap() : ?array
    {
        return $this->dataTypeMap;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $templateMap;

    public function setTemplateMap(?array $templateMap) : void
    {
        $this->templateMap = $templateMap;
    }

    public function getTemplateMap() : ?array
    {
        return $this->templateMap;
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
     * @Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $snapshotCompleted;

    public function setSnapshotCompleted(?DateTime $snapshotCompleted) : void
    {
        $this->snapshotCompleted = $snapshotCompleted;
    }

    public function getSnapshotCompleted() : ?DateTime
    {
        return $this->snapshotCompleted;
    }

    /**
     * @Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $importCompleted;

    public function setImportCompleted(?DateTime $importCompleted) : void
    {
        $this->importCompleted = $importCompleted;
    }

    public function getImportCompleted() : ?DateTime
    {
        return $this->importCompleted;
    }

    /**
     * @OneToMany(
     *     targetEntity="OsiiItem",
     *     mappedBy="import",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $osiiItems;

    public function getOsiiItems()
    {
        return $this->osiiItems;
    }

    /**
     * @OneToMany(
     *     targetEntity="OsiiMedia",
     *     mappedBy="import",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $osiiMedia;

    public function getOsiiMedia()
    {
        return $this->osiiMedia;
    }

    /**
     * @OneToMany(
     *     targetEntity="OsiiItemSet",
     *     mappedBy="import",
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $osiiItemSets;

    public function getOsiiItemSets()
    {
        return $this->osiiItemSets;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs) : void
    {
        $this->setCreated(new DateTime('now'));
    }
}
