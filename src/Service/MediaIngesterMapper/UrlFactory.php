<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Url;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Url($options['importEntity']);
    }
}
