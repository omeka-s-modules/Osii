<?php
namespace Osii\ControllerPlugin;

use Laminas\Form\Element as LaminasElement;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorInterface;

class Osii extends AbstractPlugin
{
    protected $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
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
                'label' => $dataTypeId,
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
     * Get all local property URIs.
     *
     * @return array
     */
    public function getLocalProperties()
    {
        $entityManager = $this->services->get('Omeka\EntityManager');
        $dql = '
        SELECT CONCAT(v.namespaceUri, p.localName) AS uri
        FROM Omeka\Entity\Property p
        JOIN p.vocabulary v';
        $query = $entityManager->createQuery($dql);
        return array_column($query->getResult(), 'uri');
    }

    /**
     * Get all local class URIs.
     *
     * @return array
     */
    public function getLocalClasses()
    {
        $entityManager = $this->services->get('Omeka\EntityManager');
        $dql = '
        SELECT CONCAT(v.namespaceUri, c.localName) AS uri
        FROM Omeka\Entity\ResourceClass c
        JOIN c.vocabulary v';
        $query = $entityManager->createQuery($dql);
        return array_column($query->getResult(), 'uri');
    }

    /**
     * Get all local media ingester mappers.
     *
     * @return array
     */
    public function getLocalMediaIngesterMappers()
    {
        return $this->services->get('Osii\MediaIngesterMapperManager')->getRegisteredNames();
    }

    /**
     * Prepare snapshot data types for display.
     *
     * @param array $snapshotDataTypes
     * @return array
     */
    public function getPreparedSnapshotDataTypes(array $snapshotDataTypes)
    {
        $countColumn = array_column($snapshotDataTypes, 'count');
        array_multisort($countColumn, SORT_DESC, $snapshotDataTypes);
        return $snapshotDataTypes;
    }

    /**
     * Prepare snapshot media ingesters for display.
     *
     * @param array $snapshotMediaIngesters
     * @return array
     */
    public function getPreparedSnapshotMediaIngesters(array $snapshotMediaIngesters)
    {
        $countColumn = array_column($snapshotMediaIngesters, 'count');
        array_multisort($countColumn, SORT_DESC, $snapshotMediaIngesters);
        return $snapshotMediaIngesters;
    }

    /**
     * Prepare snapshot members (properties and classes) for display.
     *
     * @param array $snapshotMembers
     * @return array
     */
    public function getPreparedSnapshotMembers(array $snapshotMembers)
    {
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
}
