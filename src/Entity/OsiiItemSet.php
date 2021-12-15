<?php
namespace Osii\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"import_id", "remote_item_set_id"}
 *         ),
 *         @UniqueConstraint(
 *             columns={"import_id", "local_item_set_id"}
 *         )
 *     }
 * )
 */
class OsiiItemSet extends AbstractEntity
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
     *     targetEntity="Osii\Entity\OsiiImport",
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $import;

    public function setImport(OsiiImport $import) : void
    {
        $this->import = $import;
    }

    public function getImport() : OsiiImport
    {
        return $this->import;
    }

    /**
     * @OneToOne(
     *     targetEntity="Omeka\Entity\ItemSet",
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $localItemSet;

    public function setLocalItemSet(?ItemSet $localItemSet) : void
    {
        $this->localItemSet = $localItemSet;
    }

    public function getLocalItemSet() : ?ItemSet
    {
        return $this->localItemSet;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotItemSet;

    public function setSnapshotItemSet(?array $snapshotItemSet) : void
    {
        $this->snapshotItemSet = $snapshotItemSet;
    }

    public function getSnapshotItemSet() : ?array
    {
        return $this->snapshotItemSet;
    }

    /**
     * @Column(
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    protected $remoteItemSetId;

    public function setRemoteItemSetId(int $remoteItemSetId) : void
    {
        $this->remoteItemSetId = $remoteItemSetId;
    }

    public function getRemoteItemSetId() : int
    {
        return $this->remoteItemSetId;
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
