<?php
namespace Osii\Form;

use Laminas\Form\Form;

class StopImportForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Confirm stop import', // @translate
            ],
        ]);

        // Disable the submit button if the import can't be stopped.
        $import = $this->getOption('import');
        if (!$import->canStopImport()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
