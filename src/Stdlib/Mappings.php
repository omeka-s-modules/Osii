<?php
namespace Osii\Stdlib;

class Mappings
{
    /**
     * Set a mapping.
     *
     * @param string $mapName The name of the map
     * @param mixed $sourceId The entire map array, or the source ID
     * @param mixed|null $targetId The target ID
     */
    public function set($mapName, $sourceId, $targetId = null)
    {
        if (is_array($sourceId)) {
            $this->$mapName = $sourceId;
        } else {
            $this->$mapName[$sourceId] = $targetId;
        }
    }

    /**
     * Get a mapping.
     *
     * @param string $mapName The name of the map
     * @param mixed $sourceId The source ID
     * @return The entire map array, or the target ID
     */
    public function get($mapName, $sourceId = null)
    {
        if (null === $sourceId) {
            return $this->$mapName;
        }
        return $this->$mapName[$sourceId] ?? null;
    }
}
