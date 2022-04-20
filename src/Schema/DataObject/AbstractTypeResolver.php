<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;
use Exception;

/**
 * Used for unions and interfaces to map a class instance to a type
 */
class AbstractTypeResolver
{
    /**
     * @param $obj
     * @param $context
     * @return string
     * @throws SchemaBuilderException
     * @throws Exception
     */
    public static function resolveType($obj, $context): string
    {
        $class = get_class($obj);
        $schemaContext = SchemaConfigProvider::get($context);

        while ($class && !$schemaContext->hasModel($class)) {
            if ($class === DataObject::class) {
                throw new Exception(sprintf(
                    'No models were registered in the ancestry of %s',
                    get_class($obj)
                ));
            }
            $class = get_parent_class($class ?? '');
            Schema::invariant(
                $class,
                'Could not resolve type for %s.',
                get_class($obj)
            );
        }
        return $schemaContext->getTypeNameForClass($class);
    }
}
