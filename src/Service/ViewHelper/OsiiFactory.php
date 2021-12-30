<?php
namespace Osii\Service\ViewHelper;

use Osii\ViewHelper\Osii;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OsiiFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Osii($services);
    }
}
