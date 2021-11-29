<?php


namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Core\Path;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\ORM\Connect\NullDatabaseException;

/**
 * Class Initialise
 * @package SilverStripe\GraphQL\Dev
 */
class Initialise extends Controller
{
    /**
     * @var string[]
     */
    private static $url_handlers = [
        '' => 'initialise'
    ];

    /**
     * @var string[]
     */
    private static $allowed_actions = [
        'initialise'
    ];

    /**
     * @var string
     */
    private $appNamespace;

    /**
     * @var string
     */
    private $schemaName = 'default';

    /**
     * @var string
     */
    private $graphqlConfigDir = '_graphql';

    /**
     * @var string
     */
    private $graphqlCodeDir = 'GraphQL';

    /**
     * @var string
     */
    private $endpoint = 'graphql';

    /**
     * @var string
     */
    private $projectDir = 'app';

    /**
     * @var string
     */
    private $srcDir = 'src';

    /**
     * @var string
     */
    private $perms = '0777';

    /**
     * @param HTTPRequest $request
     */
    public function initialise(HTTPRequest $request)
    {
        $isBrowser = !Director::is_cli();
        Schema::invariant(
            !$isBrowser,
            'This task can only be run from CLI'
        );
        $appNamespace = $request->getVar('namespace');
        Schema::invariant(
            $appNamespace,
            'Please provide a base namespace for your app, e.g. "namespace=App" or "namespace=MyVendor\MyProject"'
        );
        $this->appNamespace = $appNamespace;

        $this->projectDir = ModuleManifest::config()->get('project');


        $schemaName = $request->getVar('name');
        if ($schemaName) {
            $this->schemaName = $schemaName;
        }

        $graphqlConfigDir = $request->getVar('graphqlConfigDir');
        if ($graphqlConfigDir) {
            $this->graphqlConfigDir = $graphqlConfigDir;
        }

        $graphqlCodeDir = $request->getVar('graphqlCodeDir');
        if ($graphqlCodeDir) {
            $this->graphqlCodeDir = $graphqlCodeDir;
        }

        $endpoint = $request->getVar('endpoint');
        if ($endpoint) {
            $this->endpoint = $endpoint;
        }

        $srcDir = $request->getVar('srcDir');
        if ($srcDir) {
            $this->srcDir = $srcDir;
        }

        $absProjectDir = Path::join(BASE_PATH, $this->projectDir);
        $this->perms = fileperms($absProjectDir);

        $this->createGraphQLConfig();
        $this->createProjectConfig();
        $this->createResolvers();
    }

    /**
     * Creates the graphql schema specific config in _graphql/
     */
    private function createGraphQLConfig(): void
    {
        $absGraphQLDir = Path::join(BASE_PATH, $this->projectDir, $this->graphqlConfigDir);
        if (is_dir($absGraphQLDir)) {
            echo "Graphql config directory already exists. Skipping." . PHP_EOL;
            return;
        }
            echo "Creating graphql config directory: $this->graphqlConfigDir" . PHP_EOL;
            mkdir($absGraphQLDir, $this->perms);
        foreach (['models', 'config', 'types', 'queries', 'mutations'] as $file) {
            touch(Path::join($absGraphQLDir, "$file.yml"));
        }
            $configPath = Path::join($absGraphQLDir, 'config.yml');
            $defaultConfig = <<<YAML
resolvers:
  - $this->appNamespace\Resolvers
YAML;
        file_put_contents($configPath, $defaultConfig);
    }

    /**
     * Creates the SS config in _config/graphql.yml
     */
    private function createProjectConfig(): void
    {
        $absConfigFile = Path::join(BASE_PATH, $this->projectDir, '_config', 'graphql.yml');
        if (file_exists($absConfigFile)) {
            echo "Config file $absConfigFile already exists. Skipping." . PHP_EOL;
            return;
        }
            $defaultProjectConfig = <<<YAML
SilverStripe\Control\Director:
  rules:
    $this->endpoint: '%\$SilverStripe\GraphQL\Controller.$this->schemaName'
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    $this->schemaName:
      src:
        - $this->projectDir/$this->graphqlConfigDir
YAML;
        file_put_contents($absConfigFile, $defaultProjectConfig);
    }

    /**
     * Creates an example resolvers class for autodiscovery in app/src/GraphQL/Resolvers.php
     */
    private function createResolvers(): void
    {
        $absSrcDir = Path::join(BASE_PATH, $this->projectDir, $this->srcDir);
        $absGraphQLCodeDir = Path::join($absSrcDir, $this->graphqlCodeDir);
        $graphqlNamespace = $this->appNamespace . '\\' . str_replace('/', '\\', $this->graphqlCodeDir);
        if (is_dir($absGraphQLCodeDir)) {
            echo "GraphQL code dir $this->graphqlCodeDir already exists. Skipping" . PHP_EOL;
            return;
        }

        echo "Creating resolvers class in $graphqlNamespace" . PHP_EOL;
        mkdir($absGraphQLCodeDir, $this->perms, true);
        $resolverFile = Path::join($absGraphQLCodeDir, 'Resolvers.php');
        $resolverCode = <<<PHP
<?php

namespace $graphqlNamespace;

/**
 * Use this class to define custom resolvers. Static functions in this class
 * matching the pattern resolve<FieldName> or resolve<TypeNameFieldName>
 * will be automatically assigned to their respective fields.
 *
 * More information: https://docs.silverstripe.org/en/4/developer_guides/graphql/working_with_generic_types/resolver_discovery/#the-resolver-discovery-pattern
 */
class Resolvers
{

    public static function resolveMyQuery(\$obj, array \$args, \$context): array
    {
       // Return the result of query { myQuery { ... } }
       return [];
    }

}

PHP;
        file_put_contents($resolverFile, $resolverCode);
    }
}
