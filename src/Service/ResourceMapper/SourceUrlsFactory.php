<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\SourceUrls;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SourceUrlsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SourceUrls($options['job']);
    }
}
