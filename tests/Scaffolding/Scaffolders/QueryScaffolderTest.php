<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;

class QueryScaffolderTest extends SapphireTest
{
    public function testInheritedTypes()
    {
        $scaffolder = new SchemaScaffolder();
        $scaffolder
            ->type(FakeRedirectorPage::class);
        $scaffolder->addToManager($manager = new Manager());
        $manager->addType(new ObjectType([
            'name' => 'CustomTypeName',
            'fields' => [
                'Test' => Type::string(),
            ]
        ]));
        $query = new ItemQueryScaffolder(null, 'CustomTypeName', null, FakePage::class);
        $result = $query->scaffold($manager);
        $type = $result['type'];
        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals('CustomTypeName', $type->config['name']);

        $query = new ItemQueryScaffolder(null, null, null, FakePage::class);
        $result = $query->scaffold($manager);
        $type = $result['type'];
        $this->assertInstanceOf(UnionType::class, $type);
        $this->assertEquals(
            StaticSchema::inst()->inheritanceTypeNameForDataObject(FakePage::class),
            $type->config['name']
        );
    }
}
