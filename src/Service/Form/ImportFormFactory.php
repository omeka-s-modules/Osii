<?php
namespace Osii\Service\Form;

use Osii\Form\ImportForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ImportForm(null, $options);
    }
}
