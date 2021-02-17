<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\GraphQL\Schema\SchemaConfig;

class SchemaContextProvider implements ContextProvider
{
    use Injectable;

    const KEY = 'schemaConfig';

    /**
     * @var SchemaConfig
     */
    private $schemaConfig;

    /**
     * SchemaContextProvider constructor.
     * @param SchemaConfig $schemaConfig
     */
    public function __construct(SchemaConfig $schemaConfig)
    {
        $this->schemaConfig = $schemaConfig;
    }

    /**
     * @param array $context
     * @return mixed|null
     */
    public static function get(array $context): SchemaConfig
    {
        return $context[self::KEY] ?? new SchemaConfig();
    }

    /**
     * @return array[]
     */
    public function provideContext(): array
    {
        return [
            self::KEY => $this->schemaConfig,
        ];
    }
}
