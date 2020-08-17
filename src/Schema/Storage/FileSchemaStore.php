<?php

namespace SilverStripe\GraphQL\Schema\Storage;

use Exception;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
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
class FileSchemaStore implements SchemaStorageInterface
{

    use Configurable;

    /**
     * @var string
     * @config
     */
    private static $schemaFilename = '__graphql-schema.php';

    /**
     * @var array
     */
    private static $dependencies = [
        'Cache' => '%$' . CacheInterface::class . '.FileSchemaStore',
    ];

    /**
     * @var CacheInterface
     */
    private $cache;

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
        $dir = $this->getDirectory($schema->getSchemaKey());
        if (!$fs->exists($dir)) {
            $fs->mkdir($dir);
        }

        $data = ArrayData::create([
            'TypesClassName' => EncodedType::TYPE_CLASS_NAME,
            'Types' => ArrayList::create(array_values($schema->getTypes())),
            'Interfaces' => ArrayList::create(array_values($schema->getInterfaces())),
            'Unions' => ArrayList::create(array_values($schema->getUnions())),
            'Enums' => ArrayList::create(array_values($schema->getEnums())),
        ]);
        $code = (string) $data->renderWith('SilverStripe\\GraphQL\\Schema\\GraphQLTypeRegistry');
        $schemaFile = $this->getSchemaFilename($schema->getSchemaKey());
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
                $code = (string) $type->forTemplate();
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
        echo "Total types: $total\n";
        echo 'Types built: ' . count($built) . "\n";
        echo '*' . implode("\n*", $built);
        echo "\n\n";
        echo 'Types deleted: ' . count($deleted) . "\n";
        echo '*'. implode("\n*", $deleted);
        echo "\n\n";

        Benchmark::end('render', 'Code generation took %s ms');
    }

    /**
     * @param string $key
     * @return GraphQLSchema
     */
    public function getSchema(string $key): GraphQLSchema
    {
        require_once($this->getSchemaFilename($key));

        $namespace = 'SilverStripe\\GraphQL\\Schema\\Generated\\Schema';
        $registryClass = $namespace . '\\' . EncodedType::TYPE_CLASS_NAME;

        $hasMutations = method_exists($registryClass, Schema::MUTATION_TYPE);
        $schemaConfig = new SchemaConfig();
        $callback = call_user_func([$registryClass, Schema::QUERY_TYPE]);
        $schemaConfig->setQuery($callback);
        $schemaConfig->setTypeLoader([
            'SilverStripe\\GraphQL\\Schema\\Generated\\Schema\\' . EncodedType::TYPE_CLASS_NAME,
            'get'
        ]);
        if ($hasMutations) {
            $callback = call_user_func([$registryClass, Schema::MUTATION_TYPE]);
            $schemaConfig->setMutation($callback);
        }
        return new GraphQLSchema($schemaConfig);
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
     * @return FileSchemaStore
     */
    public function setCache(CacheInterface $cache): FileSchemaStore
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getDirectory(string $key): string
    {
        return Path::join(ASSETS_PATH, 'graphql-schemas', $key);
    }

    /**
     * @param string $key
     * @return string
     */
    private function getSchemaFilename(string $key): string
    {
        return Path::join($this->getDirectory($key), $this->config()->get('schemaFilename'));
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
