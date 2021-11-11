<?php
namespace Osii\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Osii\Controller\Admin\ImportController;

class ImportControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ImportController($services->get('Omeka\EntityManager'));
    }
}
