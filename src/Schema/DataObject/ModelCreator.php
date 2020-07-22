<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelCreatorInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataObject;

class ModelCreator implements SchemaModelCreatorInterface
{
    use Injectable;

    public function appliesTo(string $class): bool
    {
        return is_subclass_of($class, DataObject::class);
    }

    public function createModel(string $class): SchemaModelInterface
    {
        return DataObjectModel::create($class);
    }
}
