<?php
namespace Osii\ResourceMapper;

class ResourceValues extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        // Map resource values.
        $mappings = $this->getJob()->getMappings();
        $remoteValues = $this->getJob()->getValuesFromResource($remoteResource);
        foreach ($remoteValues as $remoteValue) {
            $remoteValue = $this->mapValue($remoteValue);
            if (!$remoteValue) {
                // Ignore value.
                continue;
            }
            // Map value annotations.
            $localValueAnnotations = [];
            $remoteValueAnnotations = $this->getJob()->getValueAnnotationsFromValue($remoteValue);
            foreach ($remoteValueAnnotations as $remoteValueAnnotation) {
                $remoteValueAnnotation = $this->mapValue($remoteValueAnnotation);
                if (!$remoteValueAnnotation) {
                    // Ignore value annotation.
                    continue;
                }
                $localPropertyId = $mappings->get('properties', $remoteValueAnnotation['property_id']);
                $localValueAnnotations[$localPropertyId][] = $remoteValueAnnotation;
            }
            if ($localValueAnnotations) {
                $remoteValue['@annotation'] = $localValueAnnotations;
            }
            $localPropertyId = $mappings->get('properties', $remoteValue['property_id']);
            $localResource[$localPropertyId][] = $remoteValue;
        }
        return $localResource;
    }

    public function mapValue(array $value)
    {
        $mappings = $this->getJob()->getMappings();
        $localDataTypeId = $mappings->get('dataTypes', $value['type']);
        if (!$localDataTypeId) {
            // Data type is not on local installation. Ignore value.
            return false;
        }
        $localPropertyId = $mappings->get('properties', $value['property_id']);
        if (!$localPropertyId) {
            // Property is not on local installation. Ignore value.
            return false;
        }
        if (isset($value['value_resource_id'])) {
            if ('items' === $value['value_resource_name']) {
                $valueResourceId = $mappings->get('items', $value['value_resource_id']);
                if (!$valueResourceId) {
                    // Item is not on local installation. Ignore value.
                    return false;
                }
                $value['value_resource_id'] = $valueResourceId;
            } elseif ('item_sets' === $value['value_resource_name']) {
                $valueResourceId = $mappings->get('itemSets', $value['value_resource_id']);
                if (!$valueResourceId) {
                    // Item set is not on local installation. Ignore value.
                    return false;
                }
                $value['value_resource_id'] = $valueResourceId;
            } elseif ('media' === $value['value_resource_name']) {
                $valueResourceId = $mappings->get('media', $value['value_resource_id']);
                if (!$valueResourceId) {
                    // Media is not on local installation. Ignore value.
                    return false;
                }
                $value['value_resource_id'] = $valueResourceId;
            }
        }
        $value['type'] = $localDataTypeId;
        $value['property_id'] = $localPropertyId;
        return $value;
    }
}
