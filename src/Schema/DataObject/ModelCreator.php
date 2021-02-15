<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelCreatorInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\ORM\DataObject;

/**
 * Creates a model for a DataObject
 */
class ModelCreator implements SchemaModelCreatorInterface
{
    use Injectable;

    /**
     * @param string $class
     * @return bool
     */
    public function appliesTo(string $class): bool
    {
        return is_subclass_of($class, DataObject::class);
    }

    /**
     * @param string $class
     * @param SchemaConfig $context
     * @return SchemaModelInterface
     */
    public function createModel(string $class, SchemaConfig $context): SchemaModelInterface
    {
        return DataObjectModel::create($class, $context);
    }
}
