<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Youtube;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class YoutubeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Youtube($options['importEntity']);
    }
}
