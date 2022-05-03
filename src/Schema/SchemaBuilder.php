<?php


namespace SilverStripe\GraphQL\Schema;

use GraphQL\Type\Schema as GraphQLSchema;
use M1\Env\Exception\ParseException;
use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\EventDispatcher\Symfony\Event;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class SchemaBuilder
{
    use Injectable;

    private SchemaStorageCreator $storeCreator;

    public function __construct(SchemaStorageCreator $storeCreator)
    {
        $this->setStoreCreator($storeCreator);
    }

    /**
     * Retrieves the context for an already stored schema
     * which does not require booting. Useful for getting data from
     * a saved schema at request time.
     * Returns null when no stored schema can be found.
     */
    public function getConfig(string $key): ?SchemaConfig
    {
        $store = $this->storeCreator->createStore($key);
        if ($store->exists()) {
            return $store->getConfig();
        }
        return null;
    }

    /**
     * Gets a graphql-php Schema instance that can be queried
     *
     * @throws SchemaNotFoundException
     */
    public function getSchema(string $key): ?GraphQLSchema
    {
        $store = $this->getStoreCreator()->createStore($key);
        if ($store->exists()) {
            return $store->getSchema();
        }

        return null;
    }

    /**
     * Stores a schema and fetches the graphql-php instance
     *
     * @throws SchemaNotFoundException
     * @throws EmptySchemaException
     */
    public function build(Schema $schema, bool $clear = false): GraphQLSchema
    {
        $store = $this->getStoreCreator()->createStore($schema->getSchemaKey());
        if ($clear) {
            $store->clear();
        }
        $store->persistSchema($schema->createStoreableSchema());

        Dispatcher::singleton()->trigger(
            'graphqlSchemaBuild',
            Event::create($schema->getSchemaKey(), [
                'schema' => $schema
            ])
        );

        return $store->getSchema();
    }

    /**
     * Boots a schema, persists it, and fetches it
     *
     * @throws SchemaBuilderException
     * @throws SchemaNotFoundException
     * @throws EmptySchemaException
     */
    public function buildByName(string $key, bool $clear = false): GraphQLSchema
    {
        $schema = $this->boot($key);

        return $this->build($schema, $clear);
    }

    /**
     * Auto-discovers the schema based on the provided schema key
     * in Silverstripe's configuration layer. Merges the global schema
     * with specifics for this schema key.
     *
     * An instance can only be booted once to avoid conflicts with further
     * instance level modifications such as {@link addType()}.
     *
     * This method should only be used on schemas which have not been stored,
     * and is usually only needed for the process of storing them
     * (through {@link SchemaBuilder->build()}).
     *
     * @throws SchemaBuilderException
     */
    public function boot(string $key): Schema
    {
        $schemaObj = Schema::create($key);
        $schemas = $schemaObj->config()->get('schemas') ?: [];

        if (!array_key_exists($key, $schemas ?? [])) {
            throw new SchemaBuilderException(sprintf(
                'Schema "%s" has not been defined',
                $key
            ));
        }

        $schema = $schemas[$key];

        // Gather all the global config first
        $mergedSchema = $schemas[Schema::ALL] ?? [];

        // Flushless global sources
        $globalSrcs = $mergedSchema[Schema::SOURCE] ?? [];
        unset($mergedSchema[Schema::SOURCE]);
        Schema::invariant(
            is_array($globalSrcs),
            'The "src" node in the global schema must be an array. Strings are not allowed.'
        );
        Schema::assertValidConfig($globalSrcs);
        foreach ($globalSrcs as $configSrc => $data) {
            if ($data === false) {
                continue;
            }
            $sourcedConfig = self::getSchemaConfigFromSource($key, $data);
            $mergedSchema = Priority::mergeArray($sourcedConfig, $mergedSchema);
        }

        // Schema-specific flushless sources
        $configSrcs = $schema[Schema::SOURCE] ?? [];
        unset($schema[Schema::SOURCE]);
        Schema::invariant(
            is_array($configSrcs),
            'The "src" node must be an array. Strings are not allowed.'
        );

        foreach ($configSrcs as $configSrc => $data) {
            if ($data === false) {
                continue;
            }
            $sourcedConfig = self::getSchemaConfigFromSource($key, $data);
            $mergedSchema = Priority::mergeArray($sourcedConfig, $mergedSchema);
        }

        // Finally, apply the standard _config schema
        $mergedSchema = Priority::mergeArray($schema, $mergedSchema);
        $schemaObj->applyConfig($mergedSchema);

        return $schemaObj;
    }

    public function getStoreCreator(): SchemaStorageCreator
    {
        return $this->storeCreator;
    }

    public function setStoreCreator(SchemaStorageCreator $storeCreator): SchemaBuilder
    {
        $this->storeCreator = $storeCreator;
        return $this;
    }


    /**
     * Retrieves config from filesystem path.
     * Use {@link applyConfig()} to use the resulting config array on the schema instance.
     *
     * @throws SchemaBuilderException
     */
    private static function getSchemaConfigFromSource(string $schemaKey, string $dir): array
    {
        $resolvedDir = ModuleResourceLoader::singleton()->resolvePath($dir);
        $absConfigSrc = Director::is_absolute($dir) ? $dir : Path::join(BASE_PATH, $resolvedDir);

        Schema::invariant(
            !is_file($absConfigSrc ?? ''),
            'Provided source config file "%s" rather than directory on schema %s. ' .
            'See https://docs.silverstripe.org/en/4/developer_guides/graphql/getting_started/configuring_your_schema/',
            $absConfigSrc,
            $schemaKey
        );

        Schema::invariant(
            is_dir($absConfigSrc ?? ''),
            'Source config directory %s does not exist on schema %s. ' .
            'See https://docs.silverstripe.org/en/4/developer_guides/graphql/getting_started/configuring_your_schema/',
            $absConfigSrc,
            $schemaKey
        );

        $config = [
            Schema::SCHEMA_CONFIG => [],
            Schema::TYPES => [],
            Schema::BULK_LOAD => [],
            Schema::MODELS => [],
            Schema::QUERIES => [],
            Schema::MUTATIONS => [],
            Schema::ENUMS => [],
            Schema::INTERFACES => [],
            Schema::UNIONS => [],
            Schema::SCALARS => [],
        ];

        $finder = new Finder();
        $yamlFiles = $finder->files()->in($absConfigSrc)->name('*.yml');

        /* @var SplFileInfo $yamlFile */
        foreach ($yamlFiles as $yamlFile) {
            try {
                $contents = $yamlFile->getContents();
                // fail gracefully on empty files
                if (empty($contents)) {
                    continue;
                }
                $yaml = Yaml::parse($contents);
            } catch (ParseException $e) {
                throw new SchemaBuilderException(sprintf(
                    'Could not parse YAML config for schema %s on file %s. Got error: %s',
                    $schemaKey,
                    $yamlFile->getPathname(),
                    $e->getMessage()
                ));
            }
            // Friendly check to see if the config was accidentally keyed to a schema
            Schema::invariant(
                !isset($yaml[$schemaKey]),
                'Sourced config file %s does not need a schema key. It is implicitly "%s".',
                $yamlFile->getPathname(),
                $schemaKey
            );
            // If the file is in the root src dir, e.g. _graphql/models.yml,
            // then allow the filename to be the namespace.
            if ($yamlFile->getPath() === $absConfigSrc) {
                $namespace = $yamlFile->getBasename('.yml');
            } else {
                // Otherwise, the directory name is the namespace, e.g _graphql/models/myfile.yml
                $namespace = basename($yamlFile->getPath() ?? '');
            }

            // if the yaml file was in a namespace directory, e.g. "models/" or "types/", the key is implied.
            if (isset($config[$namespace])) {
                $config[$namespace] = array_merge_recursive($config[$namespace], $yaml);
            } else {
                $config = array_merge_recursive($config, $yaml);
            }
        }

        return $config;
    }
}
