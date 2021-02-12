<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use GraphQL\Type\Schema as GraphQLSchema;

class TestSchemaBuilder extends SchemaBuilder
{
    /**
     * @var string
     */
    private $id;

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
        $this->id = uniqid();
        parent::__construct(new TestStoreCreator());
    }

    public function fetch(string $key = 'test'): ?GraphQLSchema
    {
        $schemaName = $key . '-' . $this->id;
        return parent::fetch($schemaName);
    }

    /**
     * @param Schema $schema
     * @return GraphQLSchema|null
     * @throws SchemaNotFoundException
     */
    public function fetchSchema(Schema $schema): ?GraphQLSchema
    {
        return parent::fetch($schema->getSchemaKey());
    }

    /**
     * @param string $key
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function boot(string $key = 'test'): Schema
    {
        $schemaName = $key . '-' . $this->id;
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
        Config::modify()->merge(
            Schema::class,
            'schemas',
            [
                $key => [
                    'src' => array_map(function ($dir) {
                        return TestStoreCreator::$dir . '/' . $dir;
                    }, $this->configDirs),
                ],
            ]
        );
        Config::inst()->merge(
            Injector::class,
            Dispatcher::class,
            [
                'properties' => [
                    'handlers' => [
                        'graphqlTranscribe' => [
                            'off' => ['graphqlSchemaBuild']
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    private function configureSchema(Schema $schema)
    {

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
