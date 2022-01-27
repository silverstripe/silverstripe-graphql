<?php

namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\Connect\NullDatabaseException;

/**
 * A task that initialises a schema with boilerplate config and files.
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
    private $projectDir = '';

    /**
     * @var string
     */
    private $srcDir = 'src';

    /**
     * @var string
     */
    private $perms = '';

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

        if ($request->getVar('help')) {
            $this->showHelp();
            return;
        }

        $appNamespace = $request->getVar('namespace');

        if (!$appNamespace) {
            echo "Please provide a base namespace for your app, e.g. \"namespace=App\" or \"namespace=MyVendor\MyProject\".\nFor help, run \"dev/graphql/init help=1\"\n";
            return;
        }

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
    public static function resolveMyQuery(\$obj, array \$args, array \$context): array
    {
       // Return the result of query { myQuery { ... } }
       return [];
    }
}

PHP;
        file_put_contents($resolverFile, $resolverCode);
    }

    /**
     * Outputs help text to the console
     */
    private function showHelp(): void
    {
        echo <<<TXT

****
This task executes a lot of the boilerplate required to build a new GraphQL schema. It will
generate a few files in your project directory. Any files that already exist will not be
overwritten. The task can be run multiple times and is non-destructive.
****

-- Example:

$ vendor/bin/sake dev/graphql/init namespace="MyAgency\MyApp"

-- Arguments:

[namespace]: The root namespace. Required.

<name>: The name of the schema. Default: "default"

<graphqlConfigDir>: The folder where the flushless graphql config files will go. Default: "_graphql"

<graphqlCodeDir>: The subfolder of src/ where your GraphQL code (the resolver class) will go. Follows PSR-4 based on the namespace argument (default: "GraphQL")

TXT;
    }
}
