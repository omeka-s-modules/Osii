<?php
namespace Osii\Service\MediaIngesterMapper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MediaIngesterMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new $requestedName($options['importEntity']);
    }
}
