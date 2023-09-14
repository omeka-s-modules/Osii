<?php
namespace Osii\ViewHelper;

use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class Osii extends AbstractHelper
{
    protected $services;
    protected $localProperties;
    protected $localClasses;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Does this installation have this media ingester mapper?
     *
     * @param string $id
     * @return bool
     */
    public function hasMediaIngesterMapper($id)
    {
        return $this->services->get('Osii\MediaIngesterMapperManager')->has($id);
    }

    /**
     * Does this installation have this property?
     *
     * @param string $uri
     * @return bool
     */
    public function hasProperty($uri)
    {
        return in_array($uri, $this->getLocalProperties());
    }

    /**
     * Does this installation have this class?
     *
     * @param string $uri
     * @return bool
     */
    public function hasClass($uri)
    {
        return in_array($uri, $this->getLocalClasses());
    }

    /**
     * Get all local property URIs.
     *
     * @return array
     */
    public function getLocalProperties()
    {
        if ($this->localProperties) {
            return $this->localProperties;
        }
        $entityManager = $this->services->get('Omeka\EntityManager');
        $dql = '
        SELECT CONCAT(v.namespaceUri, p.localName) AS uri
        FROM Omeka\Entity\Property p
        JOIN p.vocabulary v';
        $query = $entityManager->createQuery($dql);
        $this->localProperties = array_column($query->getResult(), 'uri');
        return $this->localProperties;
    }

    /**
     * Get all local class URIs.
     *
     * @return array
     */
    public function getLocalClasses()
    {
        if ($this->localClasses) {
            return $this->localClasses;
        }
        $entityManager = $this->services->get('Omeka\EntityManager');
        $dql = '
        SELECT CONCAT(v.namespaceUri, c.localName) AS uri
        FROM Omeka\Entity\ResourceClass c
        JOIN c.vocabulary v';
        $query = $entityManager->createQuery($dql);
        $this->localClasses = array_column($query->getResult(), 'uri');
        return $this->localClasses;
    }

    /**
     * Prepare snapshot data for display.
     *
     * @param array|null $snapshotData
     * @return array
     */
    public function prepareSnapshotData($snapshotData)
    {
        if (!is_array($snapshotData)) {
            return [];
        }
        uasort($snapshotData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        return $snapshotData;
    }

    /**
     * Prepare snapshot members (properties and classes) for display.
     *
     * @param array|null $snapshotMembers
     * @return array
     */
    public function prepareSnapshotMembers($snapshotMembers)
    {
        if (!is_array($snapshotMembers)) {
            return [];
        }
        $preparedSnapshotMembers = [];
        foreach ($snapshotMembers as $memberId => $member) {
            $preparedSnapshotMembers[$member['vocabulary_id']][$memberId] = $member;
        }
        foreach ($preparedSnapshotMembers as $namespaceUri => $members) {
            $countColumn = array_column($members, 'count');
            array_multisort($countColumn, SORT_DESC, $preparedSnapshotMembers[$namespaceUri]);
        }
        return $preparedSnapshotMembers;
    }

    /**
     * Get local data type select element.
     *
     * @return LaminasElement\Select
     */
    public function getLocalDataTypeSelect()
    {
        $dataTypes = $this->services->get('Omeka\DataTypeManager');
        $localDataTypes = [];
        foreach ($dataTypes->getRegisteredNames() as $dataTypeId) {
            $dataType = $dataTypes->get($dataTypeId);
            $localDataTypes[] = [
                'value' => $dataTypeId,
                'label' => sprintf('%s — %s', $dataTypeId, $dataType->getLabel()),
                'attributes' => [
                    'title' => $dataType->getLabel(),
                    'data-label' => $dataType->getLabel(),
                ],
            ];
        }
        usort($localDataTypes, function ($a, $b) {
            return strcasecmp($a['value'], $b['value']);
        });
        $element = new LaminasElement\Select('local_data_type');
        $element->setEmptyOption('[Not mapped]'); // @translate
        $element->setValueOptions($localDataTypes);
        $element->setAttribute('class', 'local-data-type-select chosen-select');
        return $element;
    }

    /**
     * Get local template select element.
     *
     * @return LaminasElement\Select
     */
    public function getLocalTemplateSelect()
    {
        $apiManager = $this->services->get('Omeka\ApiManager');
        $localTemplates = [];
        foreach ($apiManager->search('resource_templates')->getContent() as $template) {
            $localTemplates[$template->id()] = $template->label();
        }
        $element = new LaminasElement\Select('local_template');
        $element->setEmptyOption('[Not mapped]'); // @translate
        $element->setValueOptions($localTemplates);
        $element->setAttribute('class', 'local-template-select chosen-select');
        return $element;
    }
}
