<?php
namespace Osii\ControllerPlugin;

use Laminas\Form\Element as LaminasElement;
use Osii\Form as OsiiForm;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorInterface;

class Osii extends AbstractPlugin
{
    protected $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function getFormDoSnapshot($import)
    {
        $controller = $this->getController();
        $formDoSnapshot = $controller->getForm(OsiiForm\DoSnapshotForm::class, ['import' => $import]);
        $formDoSnapshot->setAttribute('action', $controller->url()->fromRoute('admin/osii-import-id', ['action' => 'do-snapshot'], true));
        return $formDoSnapshot;
    }

    public function getFormStopSnapshot($import)
    {
        $controller = $this->getController();
        $formStopSnapshot = $controller->getForm(OsiiForm\StopSnapshotForm::class, ['import' => $import]);
        $formStopSnapshot->setAttribute('action', $controller->url()->fromRoute('admin/osii-import-id', ['action' => 'stop-snapshot'], true));
        return $formStopSnapshot;
    }

    public function getFormDoImport($import)
    {
        $controller = $this->getController();
        $formDoImport = $controller->getForm(OsiiForm\DoImportForm::class, ['import' => $import]);
        $formDoImport->setAttribute('action', $controller->url()->fromRoute('admin/osii-import-id', ['action' => 'do-import'], true));
        return $formDoImport;
    }

    public function getFormStopImport($import)
    {
        $controller = $this->getController();
        $formStopImport = $controller->getForm(OsiiForm\StopImportForm::class, ['import' => $import]);
        $formStopImport->setAttribute('action', $controller->url()->fromRoute('admin/osii-import-id', ['action' => 'stop-import'], true));
        return $formStopImport;
    }
}
