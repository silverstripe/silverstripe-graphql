<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;

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
     * @param array|null $default
     * @return array
     */
    public function findResolver(?string $typeName = null, ?string $fieldName = null, ?array $default = null): array
    {
        foreach ($this->resolverProviders as $provider) {
            $resolver = $provider->getResolverMethod($typeName, $fieldName);
            if ($resolver) {
                return [get_class($provider), $resolver];
            }
        }

        return $default ?: $this->config()->get('default_resolver');
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
