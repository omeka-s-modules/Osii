<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Iiif;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IiifFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Iiif($options['importEntity']);
    }
}
