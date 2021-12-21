<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceClass;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceClassFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceClass($options['job']);
    }
}
