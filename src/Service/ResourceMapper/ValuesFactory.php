<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\Values;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ValuesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Values($options['job']);
    }
}
