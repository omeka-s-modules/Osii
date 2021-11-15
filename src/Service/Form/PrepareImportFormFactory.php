<?php
namespace Osii\Service\Form;

use Osii\Form\PrepareImportForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PrepareImportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PrepareImportForm(null, $options);
    }
}
