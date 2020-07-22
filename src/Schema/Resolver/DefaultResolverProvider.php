<?php


namespace SilverStripe\GraphQL\Schema\Resolver;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\Interfaces\ResolverProvider;

abstract class DefaultResolverProvider implements ResolverProvider
{
    use Configurable;

    /**
     * @var int
     * @config
     */
    private static $priority = 0;

    /**
     * Doesn't matter what this value is because it should never be registered, but just in case.
     * @return int
     */
    public static function getPriority(): int
    {
        return static::config()->get('priority');
    }

    /**
     * @param string|null $typeName
     * @param string $fieldName
     * @return string|null
     */
    public static function getResolverMethod(?string $typeName = null, ?string $fieldName = null): ?string
    {
        $candidates = array_filter([
            // resolveMyTypeMyField()
            $typeName && $fieldName ?
                sprintf('resolve%s%s', ucfirst($typeName), ucfirst($fieldName)) :
                null,
            // resolveMyType()
            $typeName ? sprintf('resolve%s', ucfirst($typeName)) : null,
            // resolveMyField()
            $fieldName ? sprintf('resolve%s', ucfirst($fieldName)) : null,
            // resolve()
            'resolve',
        ]);

        foreach ($candidates as $method) {
            $callable = [static::class, $method];
            $isCallable = is_callable($callable, false);
            if ($isCallable) {
                return $method;
            }
        }

        return null;
    }
}
