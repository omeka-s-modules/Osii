<?php
namespace Osii\Service;

use Interop\Container\ContainerInterface;
use Osii\Osii;
use Zend\ServiceManager\Factory\FactoryInterface;

class OsiiFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Osii($services);
    }
}
