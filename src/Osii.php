<?php
namespace Osii;

use Zend\ServiceManager\ServiceLocatorInterface;

class Osii
{
    protected $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Do an Omeka S API request and return the JSON-LD output.
     *
     * Returns false if not a valid URL, does not resolve, or does not have the
     * Omeka-S-Version header.
     *
     * @param string $endpoint
     * @param string|null $keyIdentity
     * @param string|null $keyCredential
     * @return array|false
     */
    public function doApiRequest($endpoint, $keyIdentity = null, $keyCredential = null)
    {
        $client = $this->services->get('Omeka\HttpClient');
        $client->setUri($endpoint);
        $client->setParameterGet([
            'key_identity' => $keyIdentity,
            'key_credential' => $keyCredential,
        ]);
        try {
            $response = $client->send();
        } catch (Exception $e) {
            // Must be a valid URL.
            return false;
        }
        if (!$response->isSuccess()) {
            // Must successfully resolve.
            return false;
        }
        if (!$response->getHeaders()->get('omeka-s-version')) {
            // Must have the Omeka-S-Version header.
            return false;
        }
        return json_decode($response->getBody(), true);
    }
}
