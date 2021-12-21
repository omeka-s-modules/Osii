<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceValues;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceValuesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceValues($options['job']);
    }
}
