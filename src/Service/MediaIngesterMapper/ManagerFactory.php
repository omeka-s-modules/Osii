<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Manager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new Manager($services, $config['osii_media_ingester_mappers']);
    }
}
