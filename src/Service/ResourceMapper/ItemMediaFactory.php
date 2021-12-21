<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ItemMedia;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ItemMediaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ItemMedia($options['job']);
    }
}
