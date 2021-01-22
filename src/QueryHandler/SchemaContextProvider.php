<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaContext;

class SchemaContextProvider implements ContextProvider
{
    use Injectable;

    const KEY = 'schemaContext';

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param array $context
     * @return mixed|null
     */
    public static function get(array $context): SchemaContext
    {
        return $context[self::KEY] ?? new SchemaContext();
    }

    /**
     * @return array[]
     */
    public function provideContext(): array
    {
        return [
            self::KEY => $this->schema->getStore()->getContext(),
        ];
    }
}
