<?php

namespace DoctrineProxies\__CG__\Osii\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class OsiiImport extends \Osii\Entity\OsiiImport implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array<string, null> properties to be lazy loaded, indexed by property name
     */
    public static $lazyPropertiesNames = array (
);

    /**
     * @var array<string, mixed> default values of properties to be lazy loaded, with keys being the property names
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array (
);



    public function __construct(?\Closure $initializer = null, ?\Closure $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', 'id', 'owner', 'localItemSet', 'snapshotJob', 'importJob', 'label', 'rootEndpoint', 'keyIdentity', 'keyCredential', 'remoteQuery', 'deleteRemovedItems', 'deleteRemovedMedia', 'deleteRemovedItemSets', 'addSourceResource', 'sourceSite', 'snapshotItems', 'snapshotMedia', 'snapshotItemSets', 'snapshotDataTypes', 'snapshotProperties', 'snapshotClasses', 'snapshotVocabularies', 'snapshotMediaIngesters', 'dataTypeMap', 'created', 'modified', 'snapshotCompleted', 'importCompleted', 'osiiItems', 'osiiMedia', 'osiiItemSets'];
        }

        return ['__isInitialized__', 'id', 'owner', 'localItemSet', 'snapshotJob', 'importJob', 'label', 'rootEndpoint', 'keyIdentity', 'keyCredential', 'remoteQuery', 'deleteRemovedItems', 'deleteRemovedMedia', 'deleteRemovedItemSets', 'addSourceResource', 'sourceSite', 'snapshotItems', 'snapshotMedia', 'snapshotItemSets', 'snapshotDataTypes', 'snapshotProperties', 'snapshotClasses', 'snapshotVocabularies', 'snapshotMediaIngesters', 'dataTypeMap', 'created', 'modified', 'snapshotCompleted', 'importCompleted', 'osiiItems', 'osiiMedia', 'osiiItemSets'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (OsiiImport $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy::$lazyPropertiesDefaults as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @deprecated no longer in use - generated code now relies on internal components rather than generated public API
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setOwner(\Omeka\Entity\User $owner = NULL): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOwner', [$owner]);

        parent::setOwner($owner);
    }

    /**
     * {@inheritDoc}
     */
    public function getOwner(): ?\Omeka\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOwner', []);

        return parent::getOwner();
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalItemSet(\Omeka\Entity\ItemSet $localItemSet = NULL): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLocalItemSet', [$localItemSet]);

        parent::setLocalItemSet($localItemSet);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalItemSet(): ?\Omeka\Entity\ItemSet
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLocalItemSet', []);

        return parent::getLocalItemSet();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotJob(\Omeka\Entity\Job $snapshotJob = NULL): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotJob', [$snapshotJob]);

        parent::setSnapshotJob($snapshotJob);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotJob(): ?\Omeka\Entity\Job
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotJob', []);

        return parent::getSnapshotJob();
    }

    /**
     * {@inheritDoc}
     */
    public function setImportJob(\Omeka\Entity\Job $importJob = NULL): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setImportJob', [$importJob]);

        parent::setImportJob($importJob);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportJob(): ?\Omeka\Entity\Job
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getImportJob', []);

        return parent::getImportJob();
    }

    /**
     * {@inheritDoc}
     */
    public function setLabel(string $label): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLabel', [$label]);

        parent::setLabel($label);
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLabel', []);

        return parent::getLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function setRootEndpoint(string $rootEndpoint): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRootEndpoint', [$rootEndpoint]);

        parent::setRootEndpoint($rootEndpoint);
    }

    /**
     * {@inheritDoc}
     */
    public function getRootEndpoint(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRootEndpoint', []);

        return parent::getRootEndpoint();
    }

    /**
     * {@inheritDoc}
     */
    public function setKeyIdentity(?string $keyIdentity): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setKeyIdentity', [$keyIdentity]);

        parent::setKeyIdentity($keyIdentity);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeyIdentity(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getKeyIdentity', []);

        return parent::getKeyIdentity();
    }

    /**
     * {@inheritDoc}
     */
    public function setKeyCredential(?string $keyCredential): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setKeyCredential', [$keyCredential]);

        parent::setKeyCredential($keyCredential);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeyCredential(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getKeyCredential', []);

        return parent::getKeyCredential();
    }

    /**
     * {@inheritDoc}
     */
    public function setRemoteQuery(?string $remoteQuery): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRemoteQuery', [$remoteQuery]);

        parent::setRemoteQuery($remoteQuery);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteQuery(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRemoteQuery', []);

        return parent::getRemoteQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function setDeleteRemovedItems($deleteRemovedItems): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDeleteRemovedItems', [$deleteRemovedItems]);

        parent::setDeleteRemovedItems($deleteRemovedItems);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleteRemovedItems(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDeleteRemovedItems', []);

        return parent::getDeleteRemovedItems();
    }

    /**
     * {@inheritDoc}
     */
    public function setDeleteRemovedMedia($deleteRemovedMedia): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDeleteRemovedMedia', [$deleteRemovedMedia]);

        parent::setDeleteRemovedMedia($deleteRemovedMedia);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleteRemovedMedia(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDeleteRemovedMedia', []);

        return parent::getDeleteRemovedMedia();
    }

    /**
     * {@inheritDoc}
     */
    public function setDeleteRemovedItemSets($deleteRemovedItemSets): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDeleteRemovedItemSets', [$deleteRemovedItemSets]);

        parent::setDeleteRemovedItemSets($deleteRemovedItemSets);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleteRemovedItemSets(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDeleteRemovedItemSets', []);

        return parent::getDeleteRemovedItemSets();
    }

    /**
     * {@inheritDoc}
     */
    public function setAddSourceResource($addSourceResource): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAddSourceResource', [$addSourceResource]);

        parent::setAddSourceResource($addSourceResource);
    }

    /**
     * {@inheritDoc}
     */
    public function getAddSourceResource(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAddSourceResource', []);

        return parent::getAddSourceResource();
    }

    /**
     * {@inheritDoc}
     */
    public function setSourceSite(string $sourceSite): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSourceSite', [$sourceSite]);

        parent::setSourceSite($sourceSite);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceSite(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSourceSite', []);

        return parent::getSourceSite();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotItems(?array $snapshotItems): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotItems', [$snapshotItems]);

        parent::setSnapshotItems($snapshotItems);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotItems(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotItems', []);

        return parent::getSnapshotItems();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotMedia(?array $snapshotMedia): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotMedia', [$snapshotMedia]);

        parent::setSnapshotMedia($snapshotMedia);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotMedia(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotMedia', []);

        return parent::getSnapshotMedia();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotItemSets(?array $snapshotItemSets): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotItemSets', [$snapshotItemSets]);

        parent::setSnapshotItemSets($snapshotItemSets);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotItemSets(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotItemSets', []);

        return parent::getSnapshotItemSets();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotDataTypes(?array $snapshotDataTypes): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotDataTypes', [$snapshotDataTypes]);

        parent::setSnapshotDataTypes($snapshotDataTypes);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotDataTypes(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotDataTypes', []);

        return parent::getSnapshotDataTypes();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotProperties(?array $snapshotProperties): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotProperties', [$snapshotProperties]);

        parent::setSnapshotProperties($snapshotProperties);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotProperties(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotProperties', []);

        return parent::getSnapshotProperties();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotClasses(?array $snapshotClasses): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotClasses', [$snapshotClasses]);

        parent::setSnapshotClasses($snapshotClasses);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotClasses(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotClasses', []);

        return parent::getSnapshotClasses();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotVocabularies(?array $snapshotVocabularies): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotVocabularies', [$snapshotVocabularies]);

        parent::setSnapshotVocabularies($snapshotVocabularies);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotVocabularies(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotVocabularies', []);

        return parent::getSnapshotVocabularies();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotMediaIngesters(?array $snapshotMediaIngesters): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotMediaIngesters', [$snapshotMediaIngesters]);

        parent::setSnapshotMediaIngesters($snapshotMediaIngesters);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotMediaIngesters(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotMediaIngesters', []);

        return parent::getSnapshotMediaIngesters();
    }

    /**
     * {@inheritDoc}
     */
    public function setDataTypeMap(?array $dataTypeMap): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDataTypeMap', [$dataTypeMap]);

        parent::setDataTypeMap($dataTypeMap);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataTypeMap(): ?array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDataTypeMap', []);

        return parent::getDataTypeMap();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated(\DateTime $created): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreated', [$created]);

        parent::setCreated($created);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated(): \DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreated', []);

        return parent::getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function setModified(?\DateTime $modified): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setModified', [$modified]);

        parent::setModified($modified);
    }

    /**
     * {@inheritDoc}
     */
    public function getModified(): ?\DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getModified', []);

        return parent::getModified();
    }

    /**
     * {@inheritDoc}
     */
    public function setSnapshotCompleted(?\DateTime $snapshotCompleted): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSnapshotCompleted', [$snapshotCompleted]);

        parent::setSnapshotCompleted($snapshotCompleted);
    }

    /**
     * {@inheritDoc}
     */
    public function getSnapshotCompleted(): ?\DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSnapshotCompleted', []);

        return parent::getSnapshotCompleted();
    }

    /**
     * {@inheritDoc}
     */
    public function setImportCompleted(?\DateTime $importCompleted): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setImportCompleted', [$importCompleted]);

        parent::setImportCompleted($importCompleted);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportCompleted(): ?\DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getImportCompleted', []);

        return parent::getImportCompleted();
    }

    /**
     * {@inheritDoc}
     */
    public function getOsiiItems()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOsiiItems', []);

        return parent::getOsiiItems();
    }

    /**
     * {@inheritDoc}
     */
    public function getOsiiMedia()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOsiiMedia', []);

        return parent::getOsiiMedia();
    }

    /**
     * {@inheritDoc}
     */
    public function getOsiiItemSets()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOsiiItemSets', []);

        return parent::getOsiiItemSets();
    }

    /**
     * {@inheritDoc}
     */
    public function prePersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'prePersist', [$eventArgs]);

        parent::prePersist($eventArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getResourceId', []);

        return parent::getResourceId();
    }

}
