<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Oembed;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OembedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Oembed($options['importEntity']);
    }
}
