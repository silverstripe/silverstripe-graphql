<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaFactory;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;

class TestSchemaFactory extends SchemaFactory
{
    public static $schemaCount = 0;

    /**
     * @var string
     */
    public static $dir;

    /**
     * @var array
     */
    public $configDirs = [];

    /**
     * @var array
     */
    public $resolvers = [];

    /**
     * @var array
     */
    public $extraConfig = [];

    public function __construct(array $configDirs = [])
    {
        $this->configDirs = $configDirs;
    }

    public function get(string $key = 'test'): ?Schema
    {
        static::$schemaCount++;
        $schemaName = $key . '-' . static::$schemaCount;
        $schema = parent::get($schemaName);
        if ($schema) {
            $this->configureSchema($schema);
        }

        return $schema;
    }

    /**
     * @param string $key
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function boot(string $key = 'test'): Schema
    {
        static::$schemaCount++;
        $schemaName = $key . '-' . static::$schemaCount;
        $this->bootstrapSchema($schemaName);
        $schema = parent::boot($schemaName);
        $this->configureSchema($schema);

        return $schema;
    }

    /**
     * @param string $key
     */
    private function bootstrapSchema(string $key)
    {
        if (empty($this->configDirs)) {
            return;
        }

        Config::modify()->merge(
            Schema::class,
            'schemas',
            [
                $key => [
                    'src' => array_map(function ($dir) {
                        return static::$dir . '/' . $dir;
                    }, $this->configDirs),
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    private function configureSchema(Schema $schema)
    {
        /* @var CodeGenerationStore $store */
        $store = (new CodeGenerationStoreCreator())->createStore($schema->getSchemaKey());
        $store->setRootDir(static::$dir);
        $store->clear();
        $schema->setStore($store);

        $schema->getSchemaContext()->apply([
            'resolvers' => $this->resolvers
        ]);

        if (!empty($this->extraConfig)) {
            $schema->applyConfig($this->extraConfig);
        }

        // Dummy query to ensure valid schema
        $schema->addQuery(Query::create('testQuery', 'String'));
    }
}
