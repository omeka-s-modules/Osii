<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceOwner;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceOwnerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceOwner($options['job']);
    }
}
