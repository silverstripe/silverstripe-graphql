<?php


namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Executor\ExecutionResult;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Extensions\QueryRecorderExtension;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use Exception;
use GraphQL\Type\Schema;

/**
 * Enables graphql responses to be cached.
 * Internally uses QueryRecorderExtension to determine which records are queried in order to generate given responses.
 *
 * CAUTION: Experimental
 *
 * @internal
 */
class QueryCachingMiddleware implements QueryMiddleware, Flushable
{
    use Injectable;
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @inheritDoc
     */
    public function process(Schema $schema, string $query, array $context, array $vars, callable $next)
    {
        if (!DataObject::singleton()->hasExtension(QueryRecorderExtension::class)) {
            throw new Exception(sprintf(
                'You must apply the %s extension to the %s in order to use the %s middleware',
                QueryRecorderExtension::class,
                DataObject::class,
                __CLASS__
            ));
        }
        $vars = $vars['vars'] ?? [];
        $key = $this->generateCacheKey($query, $vars);

        // Get successful cache response
        $response = $this->getCachedResponse($key);
        if ($response) {
            return $response;
        }

        // Closure begins / ends recording of classes queried by DataQuery.
        // ClassSpyExtension is added to DataQuery via yml
        $spy = QueryRecorderExtension::singleton();
        list($classesUsed, $response) = $spy->recordClasses(function () use ($schema, $query, $context, $vars, $next) {
            return $next($schema, $query, $context, $vars);
        });

        // Save freshly generated response
        $this->storeCache($key, $response, $classesUsed);
        return $response;
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Generate cache key
     */
    protected function generateCacheKey(string $query, array $vars): string
    {
        return md5(var_export(
            [
                'query' => $query,
                'params' => $vars
            ],
            true
        ) ?? '');
    }

    /**
     * Get and validate cached response.
     *
     * Note: Cached responses can only be returned in array format, not object format.
     * @throws InvalidArgumentException
     */
    protected function getCachedResponse(string $key): ?array
    {
        // Initially check if the cached value exists at all
        $cache = $this->getCache();
        $cached = $cache->get($key);
        if (!isset($cached)) {
            return null;
        }

        // On cache success validate against cached classes
        foreach ($cached['classes'] as $class) {
            // Note: Could combine these clases into a UNION to cut down on extravagant queries
            // Todo: We can get last-deleted/modified as well for versioned records
            $lastEditedDate = DataObject::get($class)->max('LastEdited');
            if (strtotime($lastEditedDate ?? '') > strtotime($cached['date'] ?? '')) {
                // class modified, fail validation of cache
                return null;
            }
        }

        // On cache success + validation
        return $cached['response'];
    }

    /**
     * Send a successful response to the cache
     *
     * @param ExecutionResult|array $response
     * @throws InvalidArgumentException
     */
    protected function storeCache(string $key, $response, array $classesUsed): void
    {
        // Ensure we store serialisable version of result
        if ($response instanceof ExecutionResult) {
            $handler = Injector::inst()->get(QueryHandlerInterface::class);
            $response = $handler->serialiseResult($response);
        }

        // Don't store an error response
        $errors = $response['errors'] ?? [];
        if (!empty($errors)) {
            return;
        }

        $this->getCache()->set($key, [
            'classes' => $classesUsed,
            'response' => $response,
            'date' => DBDatetime::now()->getValue()
        ]);
    }

    public static function flush()
    {
        static::singleton()->getCache()->clear();
    }
}
