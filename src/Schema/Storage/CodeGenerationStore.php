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
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class FileSchemaStore
 */
class CodeGenerationStore implements SchemaStorageInterface
{
    use Injectable;
    use Configurable;

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
     */
    private $name;

    /**
     * @var CacheInterface
     */
    private $cache;

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
     */
    public function persistSchema(Schema $schema): void
    {
        Benchmark::start('render');
        $fs = new Filesystem();
        $finder = new Finder();
        $dir = $this->getDirectory();
        if (!$fs->exists($dir)) {
            $fs->mkdir($dir);
        }
        $globals = [
            'TypesClassName' => EncodedType::TYPE_CLASS_NAME,
            'Namespace' => $this->getNamespace(),
        ];
        $data = ArrayData::create([
            'Types' => ArrayList::create(array_values($schema->getTypes())),
            'Interfaces' => ArrayList::create(array_values($schema->getInterfaces())),
            'Unions' => ArrayList::create(array_values($schema->getUnions())),
            'Enums' => ArrayList::create(array_values($schema->getEnums())),
        ]);
        $code = (string) $data->customise($globals)
            ->renderWith('SilverStripe\\GraphQL\\Schema\\GraphQLTypeRegistry');
        $schemaFile = $this->getSchemaFilename();
        $fs->dumpFile($schemaFile, $this->toCode($code));

        $fields = ['Types', 'Interfaces', 'Unions', 'Enums'];
        $touched = [];
        $built = [];
        $total = 0;
        foreach ($fields as $field) {
            /* @var Type|InterfaceType|UnionType|Enum $type */
            foreach ($data->$field as $type) {
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
        $reporter = $schema->getReporter();
        $reporter->info("Total types: $total");
        $reporter->info(sprintf('Types built: %s', count($built)));
        $snapshot = array_slice($built, 0, 10);
        foreach ($snapshot as $type) {
            $reporter->info('*' . $type);
        }
        $diff = count($built) - count($snapshot);
        if ($diff > 0) {
            $reporter->info(sprintf('(... and %s more)', $diff));
        }

        $reporter->info(sprintf('Types deleted: %s', count($deleted)));
        $snapshot = array_slice($deleted, 0, 10);
        foreach ($snapshot as $type) {
            $reporter->info('*' . $type);
        }
        $diff = count($deleted) - count($snapshot);
        if ($diff > 0) {
            $reporter->info(sprintf('(... and %s more)', $diff));
        }

        $reporter->info(Benchmark::end('render', 'Generated code in %sms'));
    }

    /**
     * @return GraphQLSchema
     */
    public function getSchema(): GraphQLSchema
    {
        require_once($this->getSchemaFilename());

        $registryClass = $this->getClassName(EncodedType::TYPE_CLASS_NAME);

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
    private function getDirectory(): string
    {
        // TODO: temporary hack to ensure we have a writable directory.
        // Need to figure out where this executable code can go, e.g. TEMP_FOLDER?
        return Path::join(ASSETS_PATH, 'graphql-schemas', $this->name);
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
        return "<?php\n\n{$code}";
    }
}
