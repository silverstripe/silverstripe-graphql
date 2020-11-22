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
    /**
     * @param string $className
     * @param string|null $typeName
     * @param Field|null $field
     * @return string|null
     */
    public static function getResolverMethod(string $className, ?string $typeName = null, ?Field $field = null): ?string
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
            $callable = [$className, $method];
            $isCallable = is_callable($callable, false);
            if ($isCallable) {
                return $method;
            }
        }

        return null;
    }
}
