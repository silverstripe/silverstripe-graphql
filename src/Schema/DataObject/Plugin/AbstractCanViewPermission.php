<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;

/**
 * Defines a permission checking plugin for queries. Subclasses just need to
 * provide a resolver function
 */
abstract class AbstractCanViewPermission implements ModelQueryPlugin
{
    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        Schema::invariant(
            is_subclass_of(
                $query->getModel()->getSourceClass(),
                DataObject::class
            ),
            'The %s plugin can only be applied to queries that return dataobjects',
            $this->getIdentifier()
        );

        $query->addResolverAfterware(
            $this->getPermissionResolver()
        );
    }

    abstract protected function getPermissionResolver(): array;
}
