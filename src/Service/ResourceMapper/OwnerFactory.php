<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\Owner;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OwnerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Owner($options['job']);
    }
}
