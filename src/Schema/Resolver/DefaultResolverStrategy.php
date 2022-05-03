<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ResolverProvider;

/**
 * A good starting point for a resolver discovery implementation.
 * Can be subclassed with resolveMyTypeMyField() methods, etc.
 */
class DefaultResolverStrategy
{
    public static function getResolverMethod(array $resolverClasses, ?string $typeName = null, ?Field $field = null): ?callable
    {
        $fieldName = $field->getName();
        $candidates = array_filter([

            // resolveHomePageContent()
            $typeName && $fieldName ?
                sprintf('resolve%s%s', ucfirst($typeName), ucfirst($fieldName)) :
                null,

            // resolveHomePage()
            $typeName ? sprintf('resolve%s', ucfirst($typeName)) : null,

            // resolveDataObjectContent()
            /* @var ModelField $field */
            $field instanceof ModelField ? sprintf(
                'resolve%s%s',
                ucfirst($field->getModel()->getIdentifier()),
                ucfirst($fieldName)
            ) : null,

            // resolveContent()
            $fieldName ? sprintf('resolve%s', ucfirst($fieldName)) : null,

            // resolve()
            'resolve',
        ]);

        foreach ($candidates as $method) {
            foreach ($resolverClasses as $className) {
                $callable = [$className, $method];
                $isCallable = is_callable($callable, false);
                if ($isCallable) {
                    return $callable;
                }
            }
        }

        return null;
    }
}
