<?php
namespace Osii\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Osii\Api\Representation\OsiiImportRepresentation;
use Osii\Entity\OsiiImport;

class OsiiImportAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'osii_imports';
    }

    public function getRepresentationClass()
    {
        return OsiiImportRepresentation::class;
    }

    public function getEntityClass()
    {
        return OsiiImport::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (isset($data['o-module-osii:local_item_set']) && !isset($data['o-module-osii:local_item_set']['o:id'])) {
            $errorStore->addError('o-module-osii:local_item_set', 'Invalid local item set format passed in request.'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::UPDATE === $request->getOperation()) {
            $entity->setModified(new DateTime('now'));
        }
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:root_endpoint')) {
            $rootEndpoint = rtrim($request->getValue('o-module-osii:root_endpoint'), '/');
            $entity->setRootEndpoint($rootEndpoint);
        }
        if ($this->shouldHydrate($request, 'o-module-osii:key_identity')) {
            $entity->setKeyIdentity($request->getValue('o-module-osii:key_identity'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:key_credential')) {
            $entity->setKeyCredential($request->getValue('o-module-osii:key_credential'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:remote_query')) {
            $entity->setRemoteQuery($request->getValue('o-module-osii:remote_query'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:local_item_set')) {
            $itemSet = $request->getValue('o-module-osii:local_item_set');
            $entity->setLocalItemSet(
                $itemSet['o:id']
                ? $this->getAdapter('item_sets')->findEntity($itemSet['o:id'])
                : null
            );
        }
        if ($this->shouldHydrate($request, 'o-module-osii:keep_removed_resources')) {
            $entity->setKeepRemovedResources($request->getValue('o-module-osii:keep_removed_resources'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:add_source_resource')) {
            $entity->setAddSourceResource($request->getValue('o-module-osii:add_source_resource'));
        }
        if ($this->shouldHydrate($request, 'o-module-osii:source_site')) {
            $entity->setSourceSite($request->getValue('o-module-osii:source_site'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!is_string($entity->getLabel()) || '' === $entity->getLabel()) {
            $errorStore->addError('o:label', 'An import must have a label'); // @translate
        }
        if (false === $this->rootEndpointIsValid($entity->getRootEndpoint())) {
            $errorStore->addError('o-module-osii:root_endpoint', 'An import must have a valid root endpoint'); // @translate
        }
        if (false === $this->authenticationIsValid($entity->getRootEndpoint(), $entity->getKeyIdentity(), $entity->getKeyCredential())) {
            $errorStore->addError('o-module-osii:key_identity', 'An import must have valid authentication'); // @translate
            $errorStore->addError('o-module-osii:key_credential', 'An import must have valid authentication'); // @translate
        }
    }

    /**
     * Is this API root endpoint valid?
     *
     * Checks against the public "items" API resource.
     *
     * @param string $rootEndpoint
     * @return bool
     */
    protected function rootEndpointIsValid($rootEndpoint)
    {
        $endpoint = sprintf('%s/items', $rootEndpoint);
        return $this->doApiRequest($endpoint);
    }

    /**
     * Is this API authentication (key_identity & key_credential) valid?
     *
     * Checks against the private "modules" API resource.
     *
     * @param string $rootEndpoint
     * @return bool
     */
    protected function authenticationIsValid($rootEndpoint, $keyIdentity, $keyCredential)
    {
        if (!($keyIdentity && $keyCredential)) {
            // No authentication to validate.
            return true;
        }
        $endpoint = sprintf('%s/modules', $rootEndpoint);
        return $this->doApiRequest($endpoint, $keyIdentity, $keyCredential);
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
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
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
