<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceSourceUrls;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceSourceUrlsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceSourceUrls($options['job']);
    }
}
