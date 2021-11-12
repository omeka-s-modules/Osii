<?php
namespace Osii\Form;

use Laminas\Form\Form;

class StopSnapshotForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Stop snapshot', // @translate
            ],
        ]);

        // Disable the submit button if the snapshot can't be stopped.
        $import = $this->getOption('import');
        if (!$import->canStopSnapshot()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
