<?php
namespace Osii\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/osii-import', ['action' => 'browse'], true);
    }
}
