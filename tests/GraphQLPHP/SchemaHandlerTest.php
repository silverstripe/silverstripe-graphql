<?php


namespace SilverStripe\GraphQL\Tests\GraphQLPHP;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\GraphQLPHP\SchemaHandler;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Tests\Fake\TypeRegistryFake;

class SchemaHandlerTest extends SapphireTest
{
    public function testSchemaConfig()
    {
        $handler = new SchemaHandler();
        $schema = new Schema(new TypeRegistryFake());
        $config = $handler->getSchemaConfig($schema);

        $this->assertEquals('query', $config->getQuery());
        $this->assertEquals('mutation', $config->getMutation());
    }
}