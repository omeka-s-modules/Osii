<?php
namespace Osii\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Media;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"osii_item_id", "remote_media_id"}
 *         ),
 *         @UniqueConstraint(
 *             columns={"osii_item_id", "local_media_id"}
 *         )
 *     }
 * )
 */
class OsiiMedia extends AbstractEntity
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
     * @ManyToOne(
     *     targetEntity="Osii\Entity\OsiiItem",
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $osiiItem;

    public function setOsiiItem(OsiiItem $osiiItem) : void
    {
        $this->osiiItem = $osiiItem;
    }

    public function getOsiiItem() : OsiiItem
    {
        return $this->osiiItem;
    }

    /**
     * @OneToOne(
     *     targetEntity="Omeka\Entity\Media",
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $localMedia;

    public function setLocalMedia(?Media $localMedia) : void
    {
        $this->localMedia = $localMedia;
    }

    public function getLocalMedia() : ?Media
    {
        return $this->localMedia;
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
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    protected $remoteMediaId;

    public function setRemoteMediaId(int $remoteMediaId) : void
    {
        $this->remoteMediaId = $remoteMediaId;
    }

    public function getRemoteMediaId() : int
    {
        return $this->remoteMediaId;
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
