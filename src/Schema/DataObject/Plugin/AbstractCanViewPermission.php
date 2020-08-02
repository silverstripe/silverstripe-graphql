<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filterable;

abstract class AbstractCanViewPermission implements QueryPlugin
{
    /**
     * @param Query $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(Query $query, Schema $schema, array $config = []): void
    {
        Schema::invariant(
            $query instanceof ModelQuery &&
            is_subclass_of(
                $query->getModel()->getSourceClass(),
                DataObject::class
            ),
            'The %s plugin can only be applied to queries that return dataobjects',
            $this->getIdentifier()
        );

        $query->addResolverMiddleware(
            $this->getPermissionResolver()
        );
    }

    abstract protected function getPermissionResolver(): array;
}
