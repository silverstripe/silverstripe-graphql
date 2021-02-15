<?php


namespace SilverStripe\GraphQL\Tests\Schema;


use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\SchemaContext;

class NaiveSchemaBuilder extends SchemaBuilder
{
    /**
     * @var Schema[]
     */
    private $_bootedSchemas = [];

    public function __construct()
    {
    }

    /**
     * A more forgiving way of reading a stored schema. Will fallback on a boot,
     * and never leaves code gen artefacts.
     *
     * @param string $key
     * @return SchemaContext|null
     * @throws SchemaBuilderException
     */
    public function read(string $key): ?SchemaContext
    {
        $schema = $this->_bootedSchemas[$key] ?? parent::boot($key);
        $this->_bootedSchemas[$key] = $schema;

        if ($schema) {
            return $schema->getStoreableSchema()->getContext();
        }

        return null;
    }

    public static function activate(): void
    {
        Injector::inst()->load([
            SchemaBuilder::class => [
                'class' => static::class,
            ]
        ]);
    }

    public static function deactivate(): void
    {
        Injector::inst()->load([
            SchemaBuilder::class => [
                'class' => SchemaBuilder::class,
            ]
        ]);
    }

}
