<?php
namespace Osii\MediaIngesterMapper;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = MediaIngesterMapperInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        return parent::get($name, $options, $usePeeringServiceManagers);
    }
}
