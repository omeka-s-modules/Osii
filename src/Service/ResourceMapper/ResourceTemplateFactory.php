<?php
namespace Osii\Service\ResourceMapper;

use Osii\ResourceMapper\ResourceTemplate;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceTemplateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceTemplate($options['job']);
    }
}
