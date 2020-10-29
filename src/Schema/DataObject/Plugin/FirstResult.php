<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

class FirstResult implements ModelQueryPlugin
{
    const IDENTIFIER = 'firstResult';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

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

        $query->addResolverAfterware([static::class, 'firstResult']);
    }

    /**
     * @param DataList $obj
     * @return DataObject|null
     */
    public static function firstResult(DataList $obj): ?DataObject
    {
        return $obj->first();
    }
}
