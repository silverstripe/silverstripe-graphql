<?php

namespace SilverStripe\GraphQL\Schema;

use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SchemaBuilder implements ConfigurationApplier
{
    use Injectable;
    use Configurable;

    const TYPES = 'types';
    const QUERIES = 'queries';
    const MUTATIONS = 'mutations';
    const MODELS = 'models';
    const INTERFACES = 'interfaces';
    const ENUMS = 'enums';

    /**
     * @var string
     */
    private $schemaKey;

    /**
     * @var TypeAbstraction[]
     */
    private $types = [];

    /**
     * @var FieldAbstraction[]
     */
    private $queries = [];

    /**
     * @var FieldAbstraction[]
     */
    private $mutations = [];

    /**
     * @var ModelAbstraction[]
     */
    private $models = [];

    /**
     * @var InterfaceAbstraction[]
     */
    private $interfaces = [];

    /**
     * @var EnumAbstraction[]
     */
    private $enums = [];

    /**
     * Schema constructor.
     * @param string $schemaKey
     */
    public function __construct(string $schemaKey)
    {
        $this->schemaKey = $schemaKey;
    }

    /**
     * @param array $schema
     * @return SchemaBuilder
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $schema): SchemaBuilder
    {
        $types = $schema[self::TYPES] ?? [];
        $queries = $schema[self::QUERIES] ?? [];
        $mutations = $schema[self::MUTATIONS] ?? [];
        $interfaces = $schema[self::INTERFACES] ?? [];
        $models = $schema[self::MODELS] ?? [];
        $enums = $schema[self::ENUMS] ?? [];

        static::assertValidConfig($types);
        foreach ($types as $typeName => $typeConfig) {
            static::assertValidName($typeName);
            $abstract = TypeAbstraction::create($typeName, $typeConfig);
            $this->types[$typeName] = $abstract;
        }

        static::assertValidConfig($queries);
        foreach ($queries as $queryName => $queryConfig) {
            static::assertValidName($queryName);
            $abstract = FieldAbstraction::create($queryName, $queryConfig);
            $this->queries[$queryName] = $abstract;
        }

        static::assertValidConfig($mutations);
        foreach ($mutations as $mutationName => $mutationConfig) {
            static::assertValidName($mutationName);
            $abstract = FieldAbstraction::create($mutationName, $mutationConfig);
            $this->mutations[$mutationName] = $abstract;
        }

        static::assertValidConfig($interfaces);
        foreach ($interfaces as $interfaceName => $interfaceConfig) {
            static::assertValidName($interfaceName);
            $abstract = InterfaceAbstraction::create($interfaceName, $interfaceConfig);
            $this->interfaces[$interfaceName] = $abstract;
        }

        static::assertValidConfig($models);
        foreach ($models as $modelName => $modelConfig) {
            $abstract = ModelAbstraction::create($modelName, $modelConfig);
            $this->models[$modelName] = $abstract;
        }

        static::assertValidConfig($enums);
        foreach ($enums as $enumName => $enumConfig) {
            SchemaBuilder::assertValidConfig($enumConfig, ['values', 'description']);
            $values = $enumConfig['values'] ?? null;
            SchemaBuilder::invariant($values, 'No values passed to enum %s', $enumName);
            $description = $enumConfig['description'] ?? null;
            $abstract = EnumAbstraction::create($enumName, $enumConfig['values'], $description);
            $this->enums[$enumName] = $abstract;
        }

        return $this;
    }

    /**
     * @return SchemaBuilder
     * @throws SchemaBuilderException
     */
    public function loadFromConfig(): SchemaBuilder
    {
        $schemas = $this->config()->get('schemas');
        static::invariant($schemas, 'There are no schemas defined in the config');
        $schema = $schemas[$this->schemaKey] ?? null;
        static::invariant($schema, 'Schema "%s" is not configured', $this->schemaKey);

        return $this->applyConfig($schema);
    }

    public function persistSchema(): void
    {
        $schemaFileName = BASE_PATH . '/schema.php';
        $data = new ArrayData([
            'TypesClassName' => EncodedType::TYPE_CLASS_NAME,
            'Hash' => md5('UncleCheese'),
            'Types' => ArrayList::create(array_values($this->types)),
            'Queries' => ArrayList::create(array_values($this->queries)),
            'Mutations' => ArrayList::create(array_values($this->queries)),
            'Models' => ArrayList::create(array_values($this->models)),
            'Interfaces' => ArrayList::create(array_values($this->interfaces)),
            'Enums' => ArrayList::create(array_values($this->enums)),
        ]);
        $code = $data->renderWith(__NAMESPACE__ . '\\GraphQLTypeRegistry');
        $php = "<?php\n\n{$code}";
        file_put_contents($schemaFileName, $php);
    }

    public function getSchema(): Schema
    {
        $registry = require_once($schemaFileName);
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setTypeLoader(function ($type) use ($registry) {
            return $registry->getType($type);
        });
        $schemaConfig->setQuery($registry->getType('Query'));
        $schemaConfig->setMutation($registry->getType('Mutation'));

        return new Schema($schemaConfig);
    }


    /**
     * @param array $config
     * @param array $allowedKeys
     * @throws SchemaBuilderException
     */
    public static function assertValidConfig(array $config, $allowedKeys = []): void
    {
        static::invariant(
            empty($config) || ArrayLib::is_associative($config),
            '%s configurations must be key value pairs of names to configurations.
            Did you include an indexed array in your config?',
            static::class
        );

        if (!empty($allowedKeys)) {
            $invalidKeys = array_diff(array_keys($config), $allowedKeys);
            static::invariant(
                empty($invalidKeys),
                'Config contains invalid keys: %s',
                implode(',', $invalidKeys)
            );
        }
    }

    /**
     * @param $name
     * @throws SchemaBuilderException
     */
    public static function assertValidName($name): void
    {
        static::invariant(
          preg_match(' /[_A-Za-z][_0-9A-Za-z]*/', $name),
          'Invalid name: %s. Names must only use underscores and alphanumeric characters, and cannot
          begin with a number.'
        );
    }

    /**
     * @param $test
     * @param string $message
     * @param mixed ...$params
     * @throws SchemaBuilderException
     */
    public static function invariant($test, $message = '', ...$params): void
    {
        if (!$test) {
            $message = call_user_func_array('sprintf', array_merge([$message], $params));
            throw new SchemaBuilderException($message);
        }
    }
}
