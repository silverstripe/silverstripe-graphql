<?php

namespace SilverStripe\GraphQL\Schema\Storage;

use Exception;
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
     * @param Schema $schema
     * @throws Exception
     */
    public function persistSchema(Schema $schema): void
    {
        Benchmark::start('render');
        $fs = new Filesystem();
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
        foreach ($fields as $field) {
            /* @var Type|InterfaceType|UnionType|Enum $type */
            foreach ($data->$field as $type) {
                $file = Path::join($dir, $type->getName() . '.php');
                $code = (string) $type->forTemplate();
                $fs->dumpFile($file, $this->toCode($code));
            }
        }
        Benchmark::end('render', 'Code generation took %s ms');
    }

    /**
     * @param Schema $schema
     */
    public function loadRegistry(Schema $schema): void
    {
        require_once($this->getSchemaFilename($schema->getSchemaKey()));
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRegistryClassName(string $key): string
    {

        $namespace = 'SilverStripe\\GraphQL\\Schema\\Generated\\Schema';

        return $namespace . '\\' . EncodedType::TYPE_CLASS_NAME;
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
