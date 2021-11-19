<?php
namespace Osii\Job;

use Omeka\Job\AbstractJob;

abstract class AbstractOsiiJob extends AbstractJob
{
    /**
     * Get values from resource JSON-LD.
     *
     * @param array $resource
     * @return array
     */
    protected function getValuesFromResource($resource)
    {
        $resourceValues = [];
        foreach ($resource as $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                if (!is_array($value)) {
                    continue;
                }
                if (isset($value['type']) && isset($value['property_id'])) {
                    $resourceValues[] = $value;
                }
            }
        }
        return $resourceValues;
    }
}
