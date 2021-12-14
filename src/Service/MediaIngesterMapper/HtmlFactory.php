<?php
namespace Osii\Service\MediaIngesterMapper;

use Osii\MediaIngesterMapper\Html;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HtmlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Html($options['importEntity']);
    }
}
