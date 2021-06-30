<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Config\Configuration;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;

/**
 * Provides an arbitrary state container that can be passed through
 * the resolver chain. It is empty by default and derives
 * no state from the actual schema
 */
class QueryStateProvider implements ContextProvider
{
    use Injectable;

    const KEY = 'queryState';

    /**
     * @var Configuration
     */
    private $queryState;

    /**
     * QueryStateProvider constructor.
     */
    public function __construct()
    {
        $this->queryState = new Configuration();
    }

    /**
     * @param array $context
     * @return mixed|null
     */
    public static function get(array $context): Configuration
    {
        return $context[self::KEY] ?? new Configuration();
    }

    /**
     * @return array[]
     */
    public function provideContext(): array
    {
        return [
            self::KEY => $this->queryState,
        ];
    }
}
