<?php

namespace SilverStripe\GraphQL\Tests\Schema;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Storage\AbstractTypeRegistry;
use SilverStripe\GraphQL\Controller as GraphQLController;
use Symfony\Component\Filesystem\Filesystem;
use ReflectionObject;
use SilverStripe\Control\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\GraphQL\Schema\Schema;
use GraphQL\Type\Definition\ScalarType;

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
        $controller = Controller::curr();
        if ($controller instanceof GraphQLController) {
            $controller->popCurrent();
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

    #[DataProvider('provideRebuildOnMissing')]
    public function testRebuildOnMissing(
        bool $controller,
        bool $autobuild,
        bool $config,
        bool $interval,
        bool $expected
    ): void {
        list($registry, $canRebuildOnMissingMethod, $_, $getRebuildOnMissingFilename) = $this->getInstance();
        $this->prepGraphQLController($controller, $autobuild);

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

    public static function provideRebuildOnMissing(): array
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

    /**
     * This test checks no uncaught exceptions are thrown with a successful rebuild.
     * This test is required separately from the others, because the other tests explicitly
     * set some private methods to be accesible via reflection.
     */
    public function testGetRebuildOnMissing(): void
    {
        $registry = new class extends AbstractTypeRegistry
        {
            private static int $getAttempts = 0;

            protected static function getSourceDirectory(): string
            {
                return AbstractTypeRegistryTest::SOURCE_DIRECTORY;
            }

            protected static function getSourceNamespace(): string
            {
                return '';
            }

            protected static function fromCache(string $typename): bool
            {
                if (static::$getAttempts === 0) {
                    static::$getAttempts++;
                    throw new Exception('Missing graphql file for ' . $typename);
                }
                return true;
            }
        };
        $this->prepGraphQLController(true, true);
        AbstractTypeRegistry::config()->set('rebuild_on_missing_schema_file', true);
        Schema::config()->merge('schemas', [
            '.graphql-generated' => [],
        ]);

        $registry::get('test');
        // This test passes by not throwing any exceptions.
        $this->expectNotToPerformAssertions();
    }

    public function testGetReturnsBuiltinScalarWhenCacheMisses(): void
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

            protected static function fromCache(string $typename): ScalarType
            {
                throw new Exception('Missing graphql file for ' . $typename);
            }
        };

        $this->assertInstanceOf(ScalarType::class, $registry::get('Int'));
    }

    public function testGetDoesNotUseBuiltinWhenScalarOverrideExists(): void
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

            protected static function fromCache(string $typename): ScalarType
            {
                throw new Exception('Missing graphql file for ' . $typename);
            }

            public static function Int(): ScalarType
            {
                throw new Exception('schema override should be used');
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing graphql file for Int');
        $registry::get('Int');
    }

    /**
     * Prepare a GraphQLController for AbstractTypeRegistry::canRebuildOnMissing() checks.
     */
    private function prepGraphQLController(bool $controller, bool $autobuild): void
    {
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
