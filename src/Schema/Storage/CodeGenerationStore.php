<?php

namespace SilverStripe\GraphQL\Schema\Storage;

use Exception;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig as GraphQLSchemaConfig;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Logger;
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

class CodeGenerationStore implements SchemaStorageInterface
{
    use Injectable;
    use Configurable;

    const TYPE_CLASS_NAME = 'Types';

    /**
     * @config
     */
    private static string $schemaFilename = '__graphql-schema.php';

    /**
     * @config
     */
    private static string $configFilename = '__schema-config.php';

    /**
     * @config
     */
    private static string $namespacePrefix = 'SSGraphQLSchema_';

    /**
     * @config
     */
    private static string $dirName = '.graphql-generated';

    /**
     * @var string[]
     */
    private static array $dependencies = [
        'Obfuscator' => '%$' . NameObfuscator::class,
    ];

    private string $name;

    private CacheInterface $cache;

    private string $rootDir = BASE_PATH;

    private ?SchemaConfig $cachedConfig = null;

    private ?GraphQLSchema $graphqlSchema = null;

    private NameObfuscator $obfuscator;

    private bool $verbose = true;

    public function __construct(string $name, CacheInterface $cache)
    {
        $this->name = $name;
        $this->setCache($cache);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws EmptySchemaException
     */
    public function persistSchema(StorableSchema $schema): void
    {
        $logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
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
            $logger->info('Moving current schema to temp folder');
            $fs->mirror($dest, $temp);
        } else {
            $logger->info('Creating new schema');
            try {
                $fs->mkdir($temp);
                // Ensure none of these files get loaded into the manifest
                $fs->touch($temp . DIRECTORY_SEPARATOR . '_manifest_exclude');
                // Include a file to warn developers against modifying the schema manually
                $warningFile = $temp . DIRECTORY_SEPARATOR . '__DO_NOT_MODIFY';
                $fs->dumpFile(
                    $warningFile,
                    '*** This directory contains generated code for the GraphQL schema. Do not modify. ***'
                );
                // Include a file to ensure webservers don't serve the schema
                $htaccessFile = $temp . DIRECTORY_SEPARATOR . '.htaccess';
                $fs->dumpFile(
                    $htaccessFile,
                    <<<HTACCESS
                    Require all denied
                    RewriteRule .* - [F]
                    HTACCESS
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
        $obfuscator = $this->getObfuscator();
        $globals = [
            'typeClassName' => self::TYPE_CLASS_NAME,
            'namespace' => $this->getNamespace(),
            'obfuscator' => $obfuscator,
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
                $obfuscatedName = $obfuscator->obfuscate($name);
                $file = Path::join($temp, $obfuscatedName . '.php');
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
            $contents = $file->getContents();
            preg_match('/\/\/ @type:([A-Za-z0-9+_]+)/', $contents ?? '', $matches);
            Schema::invariant(
                $matches,
                'Could not find type name in file %s',
                $file->getPathname()
            );
            $type = $matches[1];
            if (!in_array($type, $touched ?? [])) {
                $fs->remove($file->getPathname());
                $this->getCache()->delete($type);
                $deleted[] = $type;
            }
        }

        // Move the new schema into the proper destination
        if ($fs->exists($dest)) {
            $logger->info('Deleting current schema');
            $fs->remove($dest);
        }

        $logger->info('Migrating new schema');
        $fs->mirror($temp, $dest);
        $logger->info('Deleting temp schema');
        $fs->remove($temp);

        $logger->info("Total types: $total");
        $logger->info(sprintf('Types built: %s', count($built ?? [])));
        $snapshot = array_slice($built ?? [], 0, 10);
        foreach ($snapshot as $type) {
            $logger->info('*' . $type);
        }
        $diff = count($built ?? []) - count($snapshot ?? []);
        if ($diff > 0) {
            $logger->info(sprintf('(... and %s more)', $diff));
        }

        $logger->info(sprintf('Types deleted: %s', count($deleted ?? [])));
        $snapshot = array_slice($deleted ?? [], 0, 10);
        foreach ($snapshot as $type) {
            $logger->info('*' . $type);
        }
        $diff = count($deleted ?? []) - count($snapshot ?? []);
        if ($diff > 0) {
            $logger->info(sprintf('(... and %s more)', $diff));
        }
    }

    /**
     * @throws SchemaNotFoundException
     */
    public function getSchema(bool $useCache = true): GraphQLSchema
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
        $hasMutations = method_exists($registryClass, Schema::MUTATION_TYPE ?? '');
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
            $this->getConfig()->get('eagerLoadTypes', []) ?? [],
            function (string $name) use ($registryClass) {
                return method_exists($registryClass, $name ?? '');
            }
        );
        $typeObjs = array_map(function (string $typeName) use ($registryClass) {
            return call_user_func([$registryClass, $typeName]);
        }, $typeNames ?? []);

        $schemaConfig->setTypes($typeObjs);

        $this->graphqlSchema = new GraphQLSchema($schemaConfig);

        return $this->graphqlSchema;
    }

    public function getConfig(): SchemaConfig
    {
        if ($this->cachedConfig) {
            return $this->cachedConfig;
        }
        $context = [];
        if (file_exists($this->getConfigFilename() ?? '')) {
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

    public function exists(): bool
    {
        return file_exists($this->getSchemaFilename() ?? '');
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function setCache(CacheInterface $cache): CodeGenerationStore
    {
        $this->cache = $cache;
        return $this;
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function setRootDir(string $rootDir): CodeGenerationStore
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    public function getObfuscator(): NameObfuscator
    {
        return $this->obfuscator;
    }

    public function setObfuscator(NameObfuscator $obfuscator): CodeGenerationStore
    {
        $this->obfuscator = $obfuscator;
        return $this;
    }

    public function setVerbose(bool $bool): self
    {
        $this->verbose = $bool;

        return $this;
    }

    public static function getTemplateDir(): string
    {
        return Path::join(__DIR__, 'templates');
    }

    private function getDirectory(): string
    {
        return Path::join(
            $this->getRootDir(),
            $this->config()->get('dirName'),
            $this->name
        );
    }

    private function getTempDirectory(): string
    {
        return Path::join(
            TEMP_FOLDER,
            $this->config()->get('dirName'),
            $this->name
        );
    }

    private function getNamespace(): string
    {
        return $this->config()->get('namespacePrefix') . md5($this->name ?? '');
    }

    private function getClassName(string $className): string
    {
        return $this->getNamespace() . '\\' . $className;
    }

    private function getSchemaFilename(): string
    {
        return Path::join(
            $this->getDirectory(),
            $this->config()->get('schemaFilename')
        );
    }

    private function getConfigFilename(): string
    {
        return Path::join(
            $this->getDirectory(),
            $this->config()->get('configFilename')
        );
    }

    private function getTempSchemaFilename(): string
    {
        return Path::join(
            $this->getTempDirectory(),
            $this->config()->get('schemaFilename')
        );
    }

    private function getTempConfigFilename(): string
    {
        return Path::join(
            $this->getTempDirectory(),
            $this->config()->get('configFilename')
        );
    }

    private function toCode(string $rawCode): string
    {
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $rawCode ?? '');
        return "<?php\n\n /** GENERATED CODE -- DO NOT MODIFY **/\n\n{$code}";
    }
}
