<?php


namespace SilverStripe\GraphQL\Schema\Registry;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\Interfaces\ResolverProvider;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;

class ResolverRegistry
{
    use Injectable;
    use Configurable;

    /**
     * @var array
     * @config
     */
    private static $default_resolver = [DefaultResolver::class, 'defaultFieldResolver'];

    /**
     * @var ResolverProvider[]
     */
    private $resolverProviders = [];

    /**
     * ResolverRegistry constructor.
     * @param ResolverProvider[] ...$providers
     */
    public function __construct(...$providers)
    {
        $this->addProviders($providers);
    }

    /**
     * @param string|null $typeName
     * @param string|null $fieldName
     * @param ResolverReference|null $default
     * @return ResolverReference
     */
    public function findResolver(
        ?string $typeName = null,
        ?string $fieldName = null,
        ?ResolverReference $default = null
    ): ResolverReference {
        foreach ($this->resolverProviders as $provider) {
            $resolver = $provider->getResolverMethod($typeName, $fieldName);
            if ($resolver) {
                return ResolverReference::create([get_class($provider), $resolver]);
            }
        }

        return $default ?: ResolverReference::create(
            $this->config()->get('default_resolver')
        );
    }

    /**
     * @param ResolverProvider[] $providers
     * @return $this
     */
    public function addProviders(array $providers): ResolverRegistry
    {
        $existing = array_map(function (ResolverProvider $provider) {
            return get_class($provider);
        }, $this->resolverProviders);

        foreach ($providers as $provider) {
            if ($provider === false) {
                continue;
            }
            if (!$provider instanceof ResolverProvider) {
                throw new InvalidArgumentException(sprintf(
                    '%s only accepts implementations of %s',
                    __CLASS__,
                    ResolverProvider::class
                ));
            }
            if (in_array(get_class($provider), $existing)) {
                continue;
            }
            $this->resolverProviders[] = $provider;
        }

        usort($this->resolverProviders, static function (ResolverProvider $a, ResolverProvider $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $this;
    }

}
