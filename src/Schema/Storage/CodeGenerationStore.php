<?php

namespace SilverStripe\GraphQL\Schema\Storage;

use Exception;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig as GraphQLSchemaConfig;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\StorableSchema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

/**
 * Class CodeGenerationStore
 */
class CodeGenerationStore implements SchemaStorageInterface
{
    use Injectable;
    use Configurable;

    const TYPE_CLASS_NAME = 'Types';

    /**
     * @var string
     * @config
     */
    private static $schemaFilename = '__graphql-schema.php';

    /**
     * @var string
     * @config
     */
    private static $configFilename = '__schema-config.php';

    /**
     * @var string
     * @config
     */
    private static $namespacePrefix = 'SSGraphQLSchema_';

    /**
     * @var string
     * @config
     */
    private static $dirName = '.graphql';

    /**
     * @var string
     */
    private $name;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $rootDir = BASE_PATH;

    /**
     * @var SchemaConfig|null
     */
    private $cachedConfig;

    /**
     * @var GraphQLSchema|null
     */
    private $graphqlSchema;

    /**
     * @param string $name
     * @param CacheInterface $cache
     */
    public function __construct(string $name, CacheInterface $cache)
    {
        $this->name = $name;
        $this->setCache($cache);
    }

    /**
     * @param StorableSchema $schema
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws EmptySchemaException
     */
    public function persistSchema(StorableSchema $schema): void
    {
        if (!$schema->exists()) {
            throw new EmptySchemaException(sprintf(
                'Schema %s is empty',
                $this->name
            ));
        }
        $schema->validate();

        $fs = new Filesystem();
        $finder = new Finder();
        $temp = $this->getTempDirectory();
        $dest = $this->getDirectory();
        if ($fs->exists($dest)) {
            Schema::message('Moving current schema to temp folder');
            $fs->mirror($dest, $temp);
        } else {
            Schema::message('Creating new schema');
            try {
                $fs->mkdir($temp);
                // Ensure none of these files get loaded into the manifest
                $fs->touch($temp . DIRECTORY_SEPARATOR . '_manifest_exclude');
                $warningFile = $temp . DIRECTORY_SEPARATOR . '__DO_NOT_MODIFY';
                $fs->dumpFile(
                    $warningFile,
                    '*** This directory contains generated code for the GraphQL schema. Do not modify. ***'
                );
            } catch (IOException $e) {
                throw new RuntimeException(sprintf(
                    'Could not persist schema. Failed to create directory %s. Full message: %s',
                    $temp,
                    $e->getMessage()
                ));
            }
        }

        $templateDir = static::getTemplateDir();
        $globals = [
            'typeClassName' => self::TYPE_CLASS_NAME,
            'namespace' => $this->getNamespace(),
        ];

        $config = $schema->getConfig()->toArray();
        $configFile = $this->getTempConfigFilename();

        try {
            $fs->dumpFile(
                $configFile,
                '<?php ' .
                PHP_EOL .
                'return ' .
                var_export($config, true) .
                ';'
            );
        } catch (IOException $e) {
            throw new RuntimeException(sprintf(
                'Could not persist schema context. Failed to write to file %s. Full message: %s',
                $configFile,
                $e->getMessage()
            ));
        }

        $allComponents = array_merge(
            $schema->getTypes(),
            $schema->getEnums(),
            $schema->getInterfaces(),
            $schema->getUnions(),
            $schema->getScalars()
        );
        $encoder = Encoder::create(Path::join($templateDir, 'registry.inc.php'), $allComponents, $globals);
        $code = $encoder->encode();
        $schemaFile = $this->getTempSchemaFilename();
        try {
            $fs->dumpFile($schemaFile, $this->toCode($code));
        } catch (IOException $e) {
            throw new RuntimeException(sprintf(
                'Could not persist schema. Failed to write to file %s. Full message: %s',
                $schemaFile,
                $e->getMessage()
            ));
        }

        $fields = [
            'Types' => 'type.inc.php',
            'Interfaces' => 'interface.inc.php',
            'Unions' => 'union.inc.php',
            'Enums' => 'enum.inc.php',
            'Scalars' => 'scalar.inc.php',
        ];
        $touched = [];
        $built = [];
        $total = 0;
        foreach ($fields as $field => $template) {
            $method = 'get' . $field;
            /* @var Type|InterfaceType|UnionType|Enum $type */
            foreach ($schema->$method() as $type) {
                $total++;
                $name = $type->getName();
                $sig = $type->getSignature();
                if ($this->getCache()->has($name)) {
                    $cached = $this->getCache()->get($name);
                    if ($sig === $cached) {
                        $touched[] = $name;
                        continue;
                    }
                }
                $file = Path::join($temp, $name . '.php');
                $encoder = Encoder::create(Path::join($templateDir, $template), $type, $globals);
                $code = $encoder->encode();
                $fs->dumpFile($file, $this->toCode($code));
                $this->getCache()->set($name, $sig);
                $touched[] = $name;
                $built[] = $name;
            }
        }
        $deleted = [];
        // Reconcile the directory for deletions
        $currentFiles = $finder
            ->files()
            ->in($temp)
            ->name('*.php')
            ->notName($this->config()->get('schemaFilename'))
            ->notName($this->config()->get('configFilename'));

        /* @var SplFileInfo $file */
        foreach ($currentFiles as $file) {
            $type = $file->getBasename('.php');
            if (!in_array($type, $touched)) {
                $fs->remove($file->getPathname());
                $this->getCache()->delete($type);
                $deleted[] = $type;
            }
        }

        // Move the new schema into the proper destination
        if ($fs->exists($dest)) {
            Schema::message('Deleting current schema');
            $fs->remove($dest);
        }

        Schema::message('Migrating new schema');
        $fs->mirror($temp, $dest);
        Schema::message('Deleting temp schema');
        $fs->remove($temp);

        Schema::message("Total types: $total");
        Schema::message(sprintf('Types built: %s', count($built)));
        $snapshot = array_slice($built, 0, 10);
        foreach ($snapshot as $type) {
            Schema::message('*' . $type);
        }
        $diff = count($built) - count($snapshot);
        if ($diff > 0) {
            Schema::message(sprintf('(... and %s more)', $diff));
        }

        Schema::message(sprintf('Types deleted: %s', count($deleted)));
        $snapshot = array_slice($deleted, 0, 10);
        foreach ($snapshot as $type) {
            Schema::message('*' . $type);
        }
        $diff = count($deleted) - count($snapshot);
        if ($diff > 0) {
            Schema::message(sprintf('(... and %s more)', $diff));
        }
    }

    /**
     * @return GraphQLSchema
     * @var bool $useCache
     * @throws SchemaNotFoundException
     */
    public function getSchema($useCache = true): GraphQLSchema
    {
        if (!$this->exists()) {
            throw new SchemaNotFoundException(sprintf(
                'Schema "%s" has not been built',
                $this->name
            ));
        }
        if ($useCache && $this->graphqlSchema) {
            return $this->graphqlSchema;
        }
        require_once($this->getSchemaFilename());

        $registryClass = $this->getClassName(self::TYPE_CLASS_NAME);
        $hasMutations = method_exists($registryClass, Schema::MUTATION_TYPE);
        $schemaConfig = new GraphqLSchemaConfig();
        $callback = call_user_func([$registryClass, Schema::QUERY_TYPE]);
        $schemaConfig->setQuery($callback);
        $schemaConfig->setTypeLoader([$registryClass, 'get']);
        if ($hasMutations) {
            $callback = call_user_func([$registryClass, Schema::MUTATION_TYPE]);
            $schemaConfig->setMutation($callback);
        }
        // Add eager loaded types
        $typeNames = array_filter(
            $this->getConfig()->get('eagerLoadTypes', []),
            function (string $name) use ($registryClass) {
                return method_exists($registryClass, $name);
            }
        );
        $typeObjs = array_map(function (string $typeName) use ($registryClass) {
            return call_user_func([$registryClass, $typeName]);
        }, $typeNames);

        $schemaConfig->setTypes($typeObjs);

        $this->graphqlSchema = new GraphQLSchema($schemaConfig);

        return $this->graphqlSchema;
    }

    /**
     * @return SchemaConfig
     */
    public function getConfig(): SchemaConfig
    {
        if ($this->cachedConfig) {
            return $this->cachedConfig;
        }
        $context = [];
        if (file_exists($this->getConfigFilename())) {
            $context = require($this->getConfigFilename());
        }
        $this->cachedConfig = new SchemaConfig($context);

        return $this->cachedConfig;
    }

    public function clear(): void
    {
        $fs = new Filesystem();
        $fs->remove($this->getDirectory());
        $this->getCache()->clear();
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->getSchemaFilename());
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     * @return CodeGenerationStore
     */
    public function setCache(CacheInterface $cache): CodeGenerationStore
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @param string $rootDir
     * @return CodeGenerationStore
     */
    public function setRootDir(string $rootDir): CodeGenerationStore
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    /**
     * @return string
     */
    public static function getTemplateDir(): string
    {
        return Path::join(__DIR__, 'templates');
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return Path::join(
            $this->getRootDir(),
            $this->config()->get('dirName'),
            $this->name
        );
    }

    /**
     * @return string
     */
    private function getTempDirectory(): string
    {
        return Path::join(
            TEMP_FOLDER,
            $this->config()->get('dirName'),
            $this->name
        );
    }

    /**
     * @return string
     */
    private function getNamespace(): string
    {
        return $this->config()->get('namespacePrefix') . md5($this->name);
    }

    /**
     * @param string $className
     * @return string
     */
    private function getClassName(string $className): string
    {
        return $this->getNamespace() . '\\' . $className;
    }

    /**
     * @return string
     */
    private function getSchemaFilename(): string
    {
        return Path::join(
            $this->getDirectory(),
            $this->config()->get('schemaFilename')
        );
    }

    /**
     * @return string
     */
    private function getConfigFilename(): string
    {
        return Path::join(
            $this->getDirectory(),
            $this->config()->get('configFilename')
        );
    }

    /**
     * @return string
     */
    private function getTempSchemaFilename(): string
    {
        return Path::join(
            $this->getTempDirectory(),
            $this->config()->get('schemaFilename')
        );
    }

    /**
     * @return string
     */
    private function getTempConfigFilename(): string
    {
        return Path::join(
            $this->getTempDirectory(),
            $this->config()->get('configFilename')
        );
    }

    /**
     * @param string $rawCode
     * @return string
     */
    private function toCode(string $rawCode): string
    {
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $rawCode);
        return "<?php\n\n /** GENERATED CODE -- DO NOT MODIFY **/\n\n{$code}";
    }
}
