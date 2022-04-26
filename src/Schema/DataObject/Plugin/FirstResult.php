<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

class FirstResult implements ModelQueryPlugin
{
    const IDENTIFIER = 'firstResult';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
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

    public static function firstResult(SS_List $obj): ?ViewableData
    {
        return $obj->first();
    }
}
