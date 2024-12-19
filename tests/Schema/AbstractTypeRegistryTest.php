<?php

namespace SilverStripe\GraphQL\Tests\Schema;

use GraphQL\Type\Definition\AbstractType;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Storage\AbstractTypeRegistry;
use SilverStripe\GraphQL\Controller as GraphQLController;
use Symfony\Component\Filesystem\Filesystem;
use ReflectionObject;
use stdClass;
use SilverStripe\Control\Session;

class AbstractTypeRegistryTest extends SapphireTest
{
    /**
     * Putting a .graphql-generated folder in a unit-test folder is similar to what IntegrationTest.php does
     * It's important that we don't overwrite the real application /.graphql-generated folder
     */
    public const SOURCE_DIRECTORY = __DIR__ . '/.graphql-generated';

    protected function setUp(): void
    {
        $dir = AbstractTypeRegistryTest::SOURCE_DIRECTORY;
        if (!file_exists($dir)) {
            mkdir($dir);
        }
    }

    protected function tearDown(): void
    {
        $dir = AbstractTypeRegistryTest::SOURCE_DIRECTORY;
        if (file_exists($dir)) {
            $fs = new Filesystem();
            $fs->remove($dir);
        }
        // ensure that any GraphqlController added to controller_stack is removed
        if (Controller::has_curr() && (Controller::curr() instanceof GraphQLController)) {
            Controller::curr()->popCurrent();
        }
    }

    public function testRebuildOnMissingPath()
    {
        list($registry, $_, $getRebuildOnMissingPathMethod, $getRebuildOnMissingFilename) = $this->getInstance();
        $this->assertSame(
            AbstractTypeRegistryTest::SOURCE_DIRECTORY . '/' . $getRebuildOnMissingFilename->invoke($registry),
            $getRebuildOnMissingPathMethod->invoke($registry)
        );
    }

    /**
     * @dataProvider provideRebuildOnMissing
     */
    public function testRebuildOnMissing(
        bool $controller,
        bool $autobuild,
        bool $config,
        bool $interval,
        bool $expected
    ): void {
        list($registry, $canRebuildOnMissingMethod, $_, $getRebuildOnMissingFilename) = $this->getInstance();
        $graphqlController = new GraphQLController('test');

        // autobuild
        $graphqlController->setAutobuildSchema($autobuild);

        // controller
        if ($controller) {
            // Make it so that Controller::curr() returns a GraphQLController
            $fakeSession = new Session([]);
            $request = Controller::curr()->getRequest();
            $request->setSession($fakeSession);
            $graphqlController->setRequest($request);
            $graphqlController->pushCurrent();
        }

        // config
        AbstractTypeRegistry::config()->set('rebuild_on_missing_schema_file', $config);

        // interval
        if ($interval) {
            // put in a timesteamp well in the past so that it's above the interval threshold
            $time = 5000;
        } else {
            // put in a timestamp that is in the future, so it can never be above the interval threshold
            $time = time() + 100;
        }
        file_put_contents(AbstractTypeRegistryTest::SOURCE_DIRECTORY . '/' . $getRebuildOnMissingFilename->invoke($registry), $time);

        // assert
        $this->assertSame($expected, $canRebuildOnMissingMethod->invoke($registry));
    }

    public function provideRebuildOnMissing(): array
    {
        // controller = current controller is a GraphQLController
        // autobuild = if autobuild is enabled
        // config = rebuild_on_missing_schema_file config is set to true
        // interval = whether it will pass the interval in seconds test
        return [
            [
                'controller' => false,
                'autobuild' => true,
                'config' => true,
                'interval' => true,
                'expected' => false,
            ],
            [
                'controller' => true,
                'autobuild' => false,
                'config' => true,
                'interval' => true,
                'expected' => false,
            ],
            [
                'controller' => true,
                'autobuild' => true,
                'config' => false,
                'interval' => true,
                'expected' => false,
            ],
            [
                'controller' => true,
                'autobuild' => true,
                'config' => true,
                'interval' => false,
                'expected' => false,
            ],
            [
                'controller' => true,
                'autobuild' => true,
                'config' => true,
                'interval' => true,
                'expected' => true,
            ],
        ];
    }

    private function getInstance()
    {
        $registry = new class extends AbstractTypeRegistry
        {
            protected static function getSourceDirectory(): string
            {
                return AbstractTypeRegistryTest::SOURCE_DIRECTORY;
            }

            protected static function getSourceNamespace(): string
            {
                return '';
            }
        };
        $reflector = new ReflectionObject($registry);
        $canRebuildOnMissingMethod = $reflector->getMethod('canRebuildOnMissing');
        $canRebuildOnMissingMethod->setAccessible(true);
        $getRebuildOnMissingPathMethod = $reflector->getMethod('getRebuildOnMissingPath');
        $getRebuildOnMissingPathMethod->setAccessible(true);
        $getRebuildOnMissingFilename = $reflector->getMethod('getRebuildOnMissingFilename');
        $getRebuildOnMissingFilename->setAccessible(true);
        return [
            $registry,
            $canRebuildOnMissingMethod,
            $getRebuildOnMissingPathMethod,
            $getRebuildOnMissingFilename
        ];
    }
}
