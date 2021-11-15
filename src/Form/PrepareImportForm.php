<?php
namespace Osii\Form;

use Laminas\Form\Form;

class PrepareImportForm extends Form
{
    public function init()
    {
        $import = $this->getOption('import');
    }
}
