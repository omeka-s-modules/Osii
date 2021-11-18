<?php
namespace Osii\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form as OmekaForm;
use Osii\Entity;
use Osii\Form as OsiiForm;
use Osii\Job;

class ImportController extends AbstractActionController
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $response = $this->api()->search('osii_imports', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $imports = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('imports', $imports);
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(OsiiForm\ImportForm::class, ['import' => null]);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o-module-osii:local_item_set'] = ['o:id' => $formData['o-module-osii:local_item_set']];
                $response = $this->api($form)->create('osii_imports', $formData);
                if ($response) {
                    $import = $response->getContent();
                    $this->messenger()->addSuccess('Import successfully added.'); // @translate
                    return $this->redirect()->toRoute('admin/osii-import-id', ['import-id' => $import->id(), 'action' => 'show'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('import', null);
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
        $form = $this->getForm(OsiiForm\ImportForm::class, ['import' => $import]);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o-module-osii:local_item_set'] = ['o:id' => $formData['o-module-osii:local_item_set']];
                $response = $this->api($form)->update('osii_imports', $import->id(), $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Import successfully edited.');
                    return $this->redirect()->toRoute('admin/osii-import-id', ['import-id' => $import->id(), 'action' => 'show'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $data = $import->getJsonLd();
            $data['o-module-osii:local_item_set'] = $data['o-module-osii:local_item_set'] ? $data['o-module-osii:local_item_set']->id() : null;
            $form->setData($data);
        }

        $view = new ViewModel;
        $view->setVariable('import', $import);
        $view->setVariable('form', $form);
        return $view;
    }

    public function showAction()
    {
        $importId = $this->params('import-id');
        $import = $this->api()->read('osii_imports', $importId)->getContent();
        $form = $this->getForm(OsiiForm\PrepareImportForm::class, ['import' => $import]);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $dataTypeMap = [];
                foreach ($postData['data_type_map'] as $dataTypes) {
                    $dataTypeMap[$dataTypes['remote']] = $dataTypes['local'];
                }
                $importEntity = $this->entityManager->find(Entity\OsiiImport::class, $importId);
                $importEntity->setDataTypeMap($dataTypeMap);
                $this->entityManager->flush();
                $this->messenger()->addSuccess('Import successfully prepared.');
                return $this->redirect()->toRoute(null, [], true);
            }
        }

        $formDoSnapshot = $this->getForm(OsiiForm\DoSnapshotForm::class, ['import' => $import]);
        $formDoSnapshot->setAttribute('action', $this->url()->fromRoute('admin/osii-import-id', ['action' => 'do-snapshot'], true));

        $formStopSnapshot = $this->getForm(OsiiForm\StopSnapshotForm::class, ['import' => $import]);
        $formStopSnapshot->setAttribute('action', $this->url()->fromRoute('admin/osii-import-id', ['action' => 'stop-snapshot'], true));

        $formDoImport = $this->getForm(OsiiForm\DoImportForm::class, ['import' => $import]);
        $formDoImport->setAttribute('action', $this->url()->fromRoute('admin/osii-import-id', ['action' => 'do-import'], true));

        $view = new ViewModel;
        $view->setVariable('import', $import);
        $view->setVariable('form', $form);
        $view->setVariable('formDoSnapshot', $formDoSnapshot);
        $view->setVariable('formStopSnapshot', $formStopSnapshot);
        $view->setVariable('formDoImport', $formDoImport);
        $view->setVariable('localDataTypeSelect', $this->osii()->getLocalDataTypeSelect());
        $view->setVariable('localProperties', $this->osii()->getLocalProperties());
        $view->setVariable('localClasses', $this->osii()->getLocalClasses());
        $view->setVariable('remoteDataTypes', $this->osii()->getPreparedSnapshotDataTypes($import->snapshotDataTypes()));
        $view->setVariable('remoteProperties', $this->osii()->getPreparedSnapshotMembers($import->snapshotProperties()));
        $view->setVariable('remoteClasses', $this->osii()->getPreparedSnapshotMembers($import->snapshotClasses()));
        return $view;
    }

    public function doSnapshotAction()
    {
        $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(OsiiForm\DoSnapshotForm::class, ['import' => $import]);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(Job\DoSnapshot::class, ['import_id' => $import->id()]);
                $importEntity = $this->entityManager->find(Entity\OsiiImport::class, $import->id());
                $importEntity->setSnapshotJob($job);
                $this->entityManager->flush();
                $this->messenger()->addSuccess('Taking snapshot. This may take a while.'); // @translate
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'show'], true);
    }

    public function stopSnapshotAction()
    {
        $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(OsiiForm\StopSnapshotForm::class, ['import' => $import]);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->jobDispatcher()->stop($import->snapshotJob()->id());
                $this->messenger()->addSuccess('Stopping snapshot.'); // @translate
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'show'], true);
    }

    public function doImportAction()
    {
        $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(OsiiForm\DoImportForm::class, ['import' => $import]);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(Job\DoImport::class, ['import_id' => $import->id()]);
                $importEntity = $this->entityManager->find(Entity\OsiiImport::class, $import->id());
                $importEntity->setImportJob($job);
                $this->entityManager->flush();
                $this->messenger()->addSuccess('Importing. This may take a while.'); // @translate
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'show'], true);
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
            $form = $this->getForm(OmekaForm\ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('osii_imports', $import->id());
                if ($response) {
                    $this->messenger()->addSuccess('Import successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/osii-import', ['action' => 'browse'], true);
    }
}
