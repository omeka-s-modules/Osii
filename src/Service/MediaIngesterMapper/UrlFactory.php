<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Upload;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Upload($options['importEntity']);
    }
}
