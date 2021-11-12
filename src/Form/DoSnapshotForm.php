<?php
namespace Osii\Form;

use Laminas\Form\Form;

class DoSnapshotForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Take snapshot', // @translate
            ],
        ]);

        // Disable the submit button if the snapshot can't be taken.
        $import = $this->getOption('import');
        if (!$import->canDoSnapshot()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
