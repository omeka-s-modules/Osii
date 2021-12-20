<?php
namespace Osii\Stdlib;

class Mappings
{
    /**
     * Set a mapping.
     *
     * @param string $mapName The name of the map
     * @param mixed $remoteId The entire map array, or the remote ID
     * @param mixed|null $localId The local ID
     */
    public function set($mapName, $remoteId, $localId = null)
    {
        if (is_array($remoteId)) {
            $this->$mapName = $remoteId;
        } else {
            $this->$mapName[$remoteId] = $localId;
        }
    }

    /**
     * Get a mapping.
     *
     * @param string $mapName The name of the map
     * @param mixed $remoteId The remote ID
     * @return The entire map array, or the local ID
     */
    public function get($mapName, $remoteId = null)
    {
        if (null === $remoteId) {
            return $this->$mapName;
        }
        return $this->$mapName[$remoteId] ?? null;
    }
}
