<?php


namespace SilverStripe\GraphQL\Schema;

use M1\Env\Exception\ParseException;
use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class SchemaFactory
{
    use Injectable;

    public function get(string $key): ?Schema
    {
        $schema = Schema::create($key);

        return $schema->isStored() ? $schema : null;
    }

    /**
     * @param string $key
     * @return Schema
     * @throws SchemaNotFoundException
     */
    public function require(string $key): Schema
    {
        $schema = static::get($key);
        if (!$schema) {
            throw new SchemaNotFoundException(sprintf(
                'Schema %s not found',
                $key
            ));
        }

        return $schema;
    }

    /**
     * Auto-discovers the schema based on the provided schema key
     * in Silverstripe's configuration layer. Merges the global schema
     * with specifics for this schema key.
     *
     * An instance can only be booted once to avoid conflicts with further
     * instance level modifications such as {@link addType()}.
     *
     * @param string $key
     * @throws SchemaBuilderException
     */
    public function boot(string $key): Schema
    {
        $schemaObj = Schema::create($key);

        $schemas = $schemaObj->config()->get('schemas') ?: [];
        $schema = $schemas[$key] ?? [];

        // Gather all the global config first
        $mergedSchema = $schemas[Schema::ALL] ?? [];

        // Flushless global sources
        $globalSrcs = $mergedSchema[Schema::SOURCE] ?? [];
        unset($mergedSchema[Schema::SOURCE]);
        if (is_string($globalSrcs)) {
            $globalSrcs = [Schema::ALL => $globalSrcs];
        }

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
        if (is_string($configSrcs)) {
            $configSrcs = [$key => $configSrcs];
        }
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

    /**
     * Retrieves config from filesystem path.
     * Use {@link applyConfig()} to use the resulting config array on the schema instance.
     *
     * @param string $schemaKey
     * @param string $dir
     * @return array
     * @throws SchemaBuilderException
     */
    private static function getSchemaConfigFromSource(string $schemaKey, string $dir): array
    {
        $resolvedDir = ModuleResourceLoader::singleton()->resolvePath($dir);
        $absConfigSrc = Director::is_absolute($dir) ? $dir : Path::join(BASE_PATH, $resolvedDir);
        Schema::invariant(
            is_dir($absConfigSrc),
            'Source config directory %s does not exist on schema %s',
            $absConfigSrc,
            $schemaKey
        );

        $config = [
            Schema::SCHEMA_CONFIG => [],
            Schema::TYPES => [],
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
                $namespace = basename($yamlFile->getPath());
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
