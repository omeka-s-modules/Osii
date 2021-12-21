<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ItemItemSets;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ItemItemSetsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ItemItemSets($options['job']);
    }
}
