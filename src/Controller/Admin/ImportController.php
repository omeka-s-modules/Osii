<?php
namespace Osii\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Osii\Form\ImportForm;

class ImportController extends AbstractActionController
{
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
        $form = $this->getForm(ImportForm::class, ['import' => null]);

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
        $form = $this->getForm(ImportForm::class, ['import' => $import]);

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
        $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();

        $view = new ViewModel;
        $view->setVariable('import', $import);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $import = $this->api()->read('osii_imports', $this->params('import-id'))->getContent();
            $form = $this->getForm(ConfirmForm::class);
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
