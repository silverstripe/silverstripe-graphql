<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\GraphQL\Dev\BuildState;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaFactory;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;
use SilverStripe\GraphQL\Schema\Type\Type;

class SchemaFactoryTest extends SapphireTest
{
    public function testGet()
    {
        $id = uniqid();
        $this->assertNull(SchemaFactory::singleton()->get('my-schema-' . $id));
        Injector::inst()->load([
            CodeGenerationStore::class => [
                'properties' => [
                    'rootDir' => __DIR__
                ]
            ],
        ]);
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

        $schema = Schema::create('my-schema-' . $id);
        $schema->addQuery(Query::create('myQuery', 'TestType'));
        $schema->save();

        $schema = SchemaFactory::singleton()->get('my-schema-' . $id);
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('my-schema-' . $id, $schema->getSchemaKey());

        $schema->getStore()->getCache()->clear();
    }

    public function testBoot()
    {
        Config::modify()->merge(Schema::class, 'schemas', [
            'my-schema' => [
                'types' => [
                    'MyType' => [
                        'fields' => [
                            'foo' => 'String',
                        ],
                    ],
                ],
            ],
        ]);

        $schema = SchemaFactory::singleton()->boot('my-schema');

        $type = $schema->getType('MyType');
        $this->assertInstanceOf(Type::class, $type);
    }
}
