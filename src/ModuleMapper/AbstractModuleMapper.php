<?php
namespace Osii\ModuleMapper;

use Osii\Entity\OsiiImport;
use Osii\Stdlib\Mappings;

abstract class AbstractModuleMapper implements ModuleMapperInterface
{
    protected $importEntity;
    protected $mappings;

    public function __construct(OsiiImport $importEntity, Mappings $mappings) {
        $this->importEntity = $importEntity;
        $this->mappings = $mappings;
    }
}
