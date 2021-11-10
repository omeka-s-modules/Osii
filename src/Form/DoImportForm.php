<?php
namespace Osii\Form;

use Laminas\Form\Form;

class DoImportForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Import', // @translate
            ],
        ]);

        // Disable the submit button if the import can't be done.
        $import = $this->getOption('import');
        if (!$import->canDoImport()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
