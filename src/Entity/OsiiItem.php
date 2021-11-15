<?php
namespace Osii\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"import_id", "remote_item_id"}
 *         ),
 *         @UniqueConstraint(
 *             columns={"import_id", "local_item_id"}
 *         )
 *     }
 * )
*/
class OsiiItem extends AbstractEntity
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
     *     targetEntity="Omeka\Entity\Item",
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="CASCADE"
     * )
     */
    protected $localItem;

    public function setLocalItem(?Item $localItem) : void
    {
        $this->localItem = $localItem;
    }

    public function getLocalItem() : ?Item
    {
        return $this->localItem;
    }

    /**
     * @Column(
     *     type="json",
     *     nullable=true
     * )
     */
    protected $snapshotItem;

    public function setSnapshotItem(?array $snapshotItem) : void
    {
        $this->snapshotItem = $snapshotItem;
    }

    public function getSnapshotItem() : ?array
    {
        return $this->snapshotItem;
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
    protected $remoteItemId;

    public function setRemoteItemId(int $remoteItemId) : void
    {
        $this->remoteItemId = $remoteItemId;
    }

    public function getRemoteItemId() : int
    {
        return $this->remoteItemId;
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
