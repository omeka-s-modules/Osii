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
                'label' => 'Import label', // @translate
                'info' => 'Enter the label of this import.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Url::class,
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
                'label' => 'Query', // @translate
                'info' => 'Enter the query used to filter the items to be imported. If no query is entered, all available items will be imported.', // @translate
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
                'label' => 'Item set', // @translate
                'info' => 'Select the item set to which imported items will be assigned.', // @translate
                'empty_option' => 'Select an item set', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Checkbox::class,
            'name' => 'o-module-osii:exclude_media',
            'options' => [
                'label' => 'Exclude media', // @translate
                'info' => 'Check this if you want the import to exclude media. If not checked, media will be imported as normal. Note that, after switching this option, you must take a snapshot and then import or changes will not take effect.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Checkbox::class,
            'name' => 'o-module-osii:exclude_item_sets',
            'options' => [
                'label' => 'Exclude item sets', // @translate
                'info' => 'Check this if you want the import to exclude item sets. If not checked, item sets will be imported as normal. Note that, after switching this option, you must take a snapshot and then import or changes will not take effect.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Checkbox::class,
            'name' => 'o-module-osii:keep_removed_resources',
            'options' => [
                'label' => 'Keep removed resources', // @translate
                'info' => 'Check this if, during import, you want to keep local resources that were removed from the remote snapshot. If checked, removed resources will remain locally but will no longer be managed by this import. If not checked, removed resources will be deleted locally as normal.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Checkbox::class,
            'name' => 'o-module-osii:add_source_resource',
            'options' => [
                'label' => 'Add remote resource URL', // @translate
                'info' => 'Check this if you want to add the remote resource\'s canonical URL to every imported resource, saved as a value using property <code>osii:source_resource</code>.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Url::class,
            'name' => 'o-module-osii:source_site',
            'options' => [
                'label' => 'Add remote site URL', // @translate
                'info' => 'Enter the URL to the site from which the imported resources are derived. If entered, this will be added to every imported resource, saved as a value using property <code>osii:source_site</code>.', // @translate
                'escape_info' => false,
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
        $inputFilter->add([
            'name' => 'o-module-osii:source_site',
            'allow_empty' => true,
        ]);
    }
}
