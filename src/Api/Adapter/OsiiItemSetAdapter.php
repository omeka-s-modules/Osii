<?php
namespace Osii\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Osii\Api\Representation\OsiiItemSetRepresentation;
use Osii\Entity\OsiiItemSet;

class OsiiItemSetAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'osii_item_sets';
    }

    public function getRepresentationClass()
    {
        return OsiiItemSetRepresentation::class;
    }

    public function getEntityClass()
    {
        return OsiiItemSet::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
    }
}
