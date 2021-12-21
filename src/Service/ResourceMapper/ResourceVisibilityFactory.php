<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceVisibility;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceVisibilityFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceVisibility($options['job']);
    }
}
