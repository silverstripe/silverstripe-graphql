<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\OperationScaffolderFake;
use SilverStripe\GraphQL\Tests\Fake\FakeResolver;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ArgumentScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Update;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use GraphQL\Type\Definition\StringType;
use Exception;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;

/**
 * @skipUpgrade
 */
class OperationScaffolderTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    public function testOperationIdentifiers()
    {
        $this->assertEquals(
            Read::class,
            OperationScaffolder::getClassFromIdentifier(SchemaScaffolder::READ)
        );
        $this->assertEquals(
            Update::class,
            OperationScaffolder::getClassFromIdentifier(SchemaScaffolder::UPDATE)
        );
        $this->assertEquals(
            Delete::class,
            OperationScaffolder::getClassFromIdentifier(SchemaScaffolder::DELETE)
        );
        $this->assertEquals(
            Create::class,
            OperationScaffolder::getClassFromIdentifier(SchemaScaffolder::CREATE)
        );
    }

    public function testOperationScaffolderArgs()
    {
        $scaffolder = new OperationScaffolderFake('testOperation', 'testType');

        $this->assertEquals('testOperation', $scaffolder->getName());

        $scaffolder->setName('changedOperation');
        $this->assertEquals('changedOperation', $scaffolder->getName());

        $scaffolder->addArgs([
            'One' => 'String',
            'Two' => 'Boolean',
        ]);
        $scaffolder->addArg(
            'One',
            'String',
            'One description',
            'One default'
        );

        $this->assertEquals([], array_diff(
            $scaffolder->getArgs()->column('argName'),
            ['One', 'Two']
        ));

        $argument = $scaffolder->getArgs()->find('argName', 'One');
        $this->assertInstanceOf(ArgumentScaffolder::class, $argument);
        $arr = $argument->toArray();
        $this->assertEquals('One description', $arr['description']);
        $this->assertEquals('One default', $arr['defaultValue']);

        $scaffolder->setArgDescriptions([
            'One' => 'Foo',
            'Two' => 'Bar',
        ]);

        $scaffolder->setArgDefaults([
            'One' => 'Feijoa',
            'Two' => 'Kiwifruit',
        ]);

        $argument = $scaffolder->getArgs()->find('argName', 'One');
        $arr = $argument->toArray();
        $this->assertEquals('Foo', $arr['description']);
        $this->assertEquals('Feijoa', $arr['defaultValue']);

        $argument = $scaffolder->getArgs()->find('argName', 'Two');
        $arr = $argument->toArray();
        $this->assertEquals('Bar', $arr['description']);
        $this->assertEquals('Kiwifruit', $arr['defaultValue']);

        $scaffolder->setArgDescription('One', 'Tui')
            ->setArgDefault('One', 'Moa');

        $argument = $scaffolder->getArgs()->find('argName', 'One');
        $arr = $argument->toArray();
        $this->assertEquals('Tui', $arr['description']);
        $this->assertEquals('Moa', $arr['defaultValue']);

        $scaffolder->removeArg('One');
        $this->assertEquals(['Two'], $scaffolder->getArgs()->column('argName'));
        $scaffolder->addArg('Test', 'String');
        $scaffolder->removeArgs(['Two', 'Test']);
        $this->assertFalse($scaffolder->getArgs()->exists());

        $ex = null;
        try {
            $scaffolder->setArgDescription('Nothing', 'Test');
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $ex);
        $this->assertRegExp('/Tried to set description/', $ex->getMessage());

        $ex = null;
        try {
            $scaffolder->setArgDefault('Nothing', 'Test');
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $ex);
        $this->assertRegExp('/Tried to set default/', $ex->getMessage());
    }

    public function testOperationScaffolderResolver()
    {
        $scaffolder = new OperationScaffolderFake('testOperation', 'testType');

        try {
            $scaffolder->setResolver(function () {
            });
            $scaffolder->setResolver(FakeResolver::class);
            $scaffolder->setResolver(new FakeResolver());
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }

        $this->assertTrue($success);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/closures, instances of/');
        $scaffolder->setResolver('fail');
    }

    public function testOperationScaffolderAppliesConfig()
    {
        $scaffolder = new OperationScaffolderFake('testOperation', 'testType');

        $scaffolder->applyConfig([
            'args' => [
                'One' => 'String',
                'Two' => [
                    'type' => 'String',
                    'default' => 'Foo',
                    'description' => 'Bar',
                ],
            ],
            'resolver' => FakeResolver::class,
            'name' => 'theGreatestOperation',
        ]);

        $this->assertEquals([], array_diff(
            $scaffolder->getArgs()->column('argName'),
            ['One', 'Two']
        ));

        $this->assertEquals('theGreatestOperation', $scaffolder->getName());

        $arg = $scaffolder->getArgs()->find('argName', 'Two');
        $this->assertInstanceof(ArgumentScaffolder::class, $arg);
        $arr = $arg->toArray();
        $this->assertInstanceOf(StringType::class, $arr['type']);

        $ex = null;
        try {
            $scaffolder->applyConfig([
                'args' => 'fail',
            ]);
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertInstanceof(Exception::class, $ex);
        $this->assertRegExp('/args must be an array/', $ex->getMessage());

        $ex = null;
        try {
            $scaffolder->applyConfig([
                'args' => [
                    'One' => [
                        'default' => 'Foo',
                        'description' => 'Bar',
                    ],
                ],
            ]);
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertInstanceof(Exception::class, $ex);
        $this->assertRegExp('/must have a type/', $ex->getMessage());

        $ex = null;
        try {
            $scaffolder->applyConfig([
                'args' => [
                    'One' => false,
                ],
            ]);
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertInstanceof(Exception::class, $ex);
        $this->assertRegExp('/should be mapped to a string or an array/', $ex->getMessage());
    }
}
