<?php
namespace Osii\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Osii\ControllerPlugin\Osii;
use Zend\ServiceManager\Factory\FactoryInterface;

class OsiiFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Osii($services);
    }
}
