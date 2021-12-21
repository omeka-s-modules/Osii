<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\Visibility;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class VisibilityFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Visibility($options['job']);
    }
}
