<?php


namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Extensions\QueryRecorderExtension;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Enables graphql responses to be cached.
 * Internally uses QueryRecorderExtension to determine which records are queried in order to generate given responses.
 *
 * CAUTION: Experimental
 *
 * @internal
 */
class QueryCachingMiddleware implements QueryMiddlewareInterface, Flushable
{
    use Injectable;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param OperationParams[] $operations
     * @param ServerConfig $config
     * @param callable $next
     * @return ExecutionResult|ExecutionResult[]
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function process($operations, ServerConfig $config, callable $next)
    {
        if (!DataObject::singleton()->hasExtension(QueryRecorderExtension::class)) {
            throw new Exception(sprintf(
                'You must apply the %s extension to the %s in order to use the %s middleware',
                QueryRecorderExtension::class,
                DataObject::class,
                __CLASS__
            ));
        }

        if (count($operations) > 1) {
            throw new Exception('You cannot use batched queries when QueryCachingMiddleware is enabled');
        }

        $operation = $operations[0];
        $key = $this->generateCacheKey($operation->query, $operation->variables);

        // Get successful cache response
        $response = $this->getCachedResponse($key);
        if ($response) {
            return $response;
        }

        // Closure begins / ends recording of classes queried by DataQuery.
        // ClassSpyExtension is added to DataQuery via yml
        $spy = QueryRecorderExtension::singleton();
        [$classesUsed, $response] = $spy->recordClasses(function () use ($operations, $config, $next) {
            return $next($operations, $config);
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

    /**
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache($cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Generate cache key
     *
     * @param string $query
     * @param array $vars
     * @return string
     */
    protected function generateCacheKey($query, $vars): string
    {
        return md5(var_export(
            [
                'query' => $query,
                'params' => $vars
            ],
            true
        ));
    }

    /**
     * Get and validate cached response.
     *
     * Note: Cached responses can only be returned in array format, not object format.
     *
     * @param string $key
     * @return ExecutionResult|ExecutionResult[]
     * @throws InvalidArgumentException
     */
    protected function getCachedResponse($key): ?array
    {
        // Initially check if the cached value exists at all
        $cache = $this->getCache();
        $cached = $cache->get($key);
        if (!isset($cached)) {
            return null;
        }

        // On cache success validate against cached classes
        foreach ($cached['classes'] as $class) {
            // Note: Could combine these classes into a UNION to cut down on extravagant queries
            // Todo: We can get last-deleted/modified as well for versioned records
            $lastEditedDate = DataObject::get($class)->max('LastEdited');
            if (strtotime($lastEditedDate) > strtotime($cached['date'])) {
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
     * @param string $key
     * @param ExecutionResult|array $response
     * @param array $classesUsed
     * @throws InvalidArgumentException
     */
    protected function storeCache($key, $response, $classesUsed): void
    {
        // Ensure we store serializable version of result
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
