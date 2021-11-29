<?php
namespace Osii\Form;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class ImportForm extends Form
{
    public function init()
    {
        $import = $this->getOption('import');

        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'o:label',
            'options' => [
                'label' => 'Label', // @translate
                'info' => 'Enter the label of this import.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'o-module-osii:root_endpoint',
            'options' => [
                'label' => 'Root endpoint', // @translate
                'info' => 'Enter the root endpoint of the API.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'o-module-osii:remote_query',
            'options' => [
                'label' => 'Remote query', // @translate
                'info' => 'Enter the remote query used to filter the items to be imported. If no query is entered, all available items will be imported.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'o-module-osii:key_identity',
            'options' => [
                'label' => 'Key identity', // @translate
                'info' => 'Enter the key_identity used to authenticate the API user.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'o-module-osii:key_credential',
            'options' => [
                'label' => 'Key credential', // @translate
                'info' => 'Enter the key_credential used to authenticate the API user.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => OmekaElement\ItemSetSelect::class,
            'name' => 'o-module-osii:local_item_set',
            'options' => [
                'label' => 'Local item set', // @translate
                'info' => 'Select the local item set to which imported items will be assigned.', // @translate
                'empty_option' => 'Select an item set', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Checkbox::class,
            'name' => 'o-module-osii:delete_removed_items',
            'options' => [
                'label' => 'Delete removed items', // @translate
                'info' => 'Check this if you want to delete local items that were removed from the remote snapshot. If not checked, removed items will remain but will no longer be managed by this import.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o-module-osii:local_item_set',
            'allow_empty' => true,
        ]);
    }
}
