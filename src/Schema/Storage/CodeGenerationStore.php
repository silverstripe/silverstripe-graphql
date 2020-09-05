<?php

namespace SilverStripe\GraphQL\Schema\Storage;

use Exception;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

/**
 * Class FileSchemaStore
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
    private static $namespacePrefix = 'SSGraphQLSchema_';

    /**
     * @var string
     * @config
     */
    private static $dirName = 'graphql-schemas';

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
     * @config
     */
    private $rootDir = BASE_PATH;

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
     * @param Schema $schema
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function persistSchema(Schema $schema): void
    {
        Benchmark::start('render');
        $fs = new Filesystem();
        $finder = new Finder();
        $dir = $this->getDirectory();
        if (!$fs->exists($dir)) {
            try {
                $fs->mkdir($dir);
                // Ensure none of these files get loaded into the manifest
                $fs->touch($dir . DIRECTORY_SEPARATOR . '_manifest_exclude');
                $warningFile = $dir . DIRECTORY_SEPARATOR . '__DO_NOT_MODIFY';
                $fs->dumpFile(
                    $warningFile,
                    '*** This directory contains generated code for the GraphQL schema. Do not modify. ***'
                );
            } catch (IOException $e) {
                throw new RuntimeException(sprintf(
                    'Could not persist schema. Failed to create directory %s. Full message: %s',
                    $this->getDirectory(),
                    $e->getMessage()
                ));
            }
        }
        $globals = [
            'TypesClassName' => self::TYPE_CLASS_NAME,
            'Namespace' => $this->getNamespace(),
        ];
        $allComponents = array_merge(
            $schema->getTypes(),
            $schema->getEnums(),
            $schema->getInterfaces(),
            $schema->getUnions()
        );
        $data = ArrayData::create([
            'SchemaComponents' => ArrayList::create($allComponents),
        ]);
        $code = (string) $data->customise($globals)
            ->renderWith('SilverStripe\\GraphQL\\Schema\\GraphQLTypeRegistry');
        $schemaFile = $this->getSchemaFilename();
        try {
            $fs->dumpFile($schemaFile, $this->toCode($code));
        } catch (IOException $e) {
            throw new RuntimeException(sprintf(
                'Could not persist schema. Failed to write to file %s. Full message: %s',
                $schemaFile,
                $e->getMessage()
            ));
        }

        $fields = ['Types', 'Interfaces', 'Unions', 'Enums'];
        $touched = [];
        $built = [];
        $total = 0;
        foreach ($fields as $field) {
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
                $file = Path::join($dir, $name . '.php');
                $code = (string) $type->customise($globals)->forTemplate($globals);
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
            ->in($dir)
            ->name('*.php')
            ->notName($this->config()->get('schemaFilename'));

        /* @var SplFileInfo $file */
        foreach ($currentFiles as $file) {
            $type = $file->getBasename('.php');
            if (!in_array($type, $touched)) {
                $fs->remove($file->getPathname());
                $this->getCache()->delete($type);
                $deleted[] = $type;
            }
        }

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

        Schema::message(Benchmark::end('render', 'Generated code in %sms'));
    }

    /**
     * @return GraphQLSchema
     */
    public function getSchema(): GraphQLSchema
    {
        require_once($this->getSchemaFilename());

        $registryClass = $this->getClassName(self::TYPE_CLASS_NAME);

        $hasMutations = method_exists($registryClass, Schema::MUTATION_TYPE);
        $schemaConfig = new SchemaConfig();
        $callback = call_user_func([$registryClass, Schema::QUERY_TYPE]);
        $schemaConfig->setQuery($callback);
        $schemaConfig->setTypeLoader([$registryClass, 'get']);
        if ($hasMutations) {
            $callback = call_user_func([$registryClass, Schema::MUTATION_TYPE]);
            $schemaConfig->setMutation($callback);
        }
        return new GraphQLSchema($schemaConfig);
    }

    public function clear(): void
    {
        $fs = new Filesystem();
        $fs->remove($this->getDirectory());
        $this->getCache()->clear();
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
     * @param string $rawCode
     * @return string
     */
    private function toCode(string $rawCode): string
    {
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $rawCode);
        return "<?php\n\n /** GENERATED CODE -- DO NOT MODIFY **/\n\n{$code}";
    }
}
