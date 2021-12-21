<?php
namespace Osii\ResourceMapper;

class Values extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $mappings = $this->getJob()->getMappings();
        $remoteValues = $this->getJob()->getValuesFromResource($remoteResource);
        foreach ($remoteValues as $remoteValue) {
            $localDataTypeId = $mappings->get('dataTypes', $remoteValue['type']);
            if (!$localDataTypeId) {
                // Data type is not on local installation. Ignore value.
                continue;
            }
            $localPropertyId = $mappings->get('properties', $remoteValue['property_id']);
            if (!$localPropertyId) {
                // Property is not on local installation. Ignore value.
                continue;
            }
            if (isset($remoteValue['value_resource_id'])) {
                if ('items' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $mappings->get('items', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Item is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                } elseif ('item_sets' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $mappings->get('itemSets', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Item set is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                } elseif ('media' === $remoteValue['value_resource_name']) {
                    $valueResourceId = $mappings->get('media', $remoteValue['value_resource_id']);
                    if (!$valueResourceId) {
                        // Media is not on local installation. Ignore value.
                        continue;
                    }
                    $remoteValue['value_resource_id'] = $valueResourceId;
                }
            }
            $remoteValue['type'] = $localDataTypeId;
            $remoteValue['property_id'] = $localPropertyId;
            if (!isset($localResource[$localPropertyId])) {
                $localResource[$localPropertyId] = [];
            }
            $localResource[$localPropertyId][] = $remoteValue;
        }
        return $localResource;
    }
}
