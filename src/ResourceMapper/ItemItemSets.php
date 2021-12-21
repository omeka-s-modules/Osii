<?php
namespace Osii\ResourceMapper;

class ItemItemSets extends AbstractResourceMapper
{
    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $resourceName = $this->getJob()->getResourceName($remoteResource);
        $mappings = $this->getJob()->getMappings();
        $importEntity = $this->getJob()->getImportEntity();

        if ('items' !== $resourceName) {
            return $localResource;
        }

        // Map remote to local item sets. Set an empty o:item_set by default
        // to remove item sets added locally since the last import.
        $localResource['o:item_set'] = [];
        foreach ($remoteResource['o:item_set'] as $remoteItemSet) {
            $itemSetId = $mappings->get('itemSets', $remoteItemSet['o:id']);
            if ($itemSetId) {
                $localResource['o:item_set'][] = ['o:id' => $itemSetId];
            }
        }
        // Add the import's local item set.
        $localItemSet = $importEntity->getLocalItemSet();
        if ($localItemSet) {
            $localResource['o:item_set'][] = ['o:id' => $localItemSet->getId()];
        }

        return $localResource;
    }
}
