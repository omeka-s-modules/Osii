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
        $element->setAttribute('class', 'local-data-type-select');
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
}
