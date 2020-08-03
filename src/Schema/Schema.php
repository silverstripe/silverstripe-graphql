<?php

namespace SilverStripe\GraphQL\Schema;

use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Field\Mutation;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Interfaces\MutationPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Interfaces\TypePlugin;
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class Schema implements ConfigurationApplier, SchemaValidator
{
    use Injectable;
    use Configurable;

    const TYPES = 'types';
    const QUERIES = 'queries';
    const MUTATIONS = 'mutations';
    const MODELS = 'models';
    const INTERFACES = 'interfaces';
    const ENUMS = 'enums';
    const QUERY_TYPE = 'Query';
    const MUTATION_TYPE = 'Mutation';
    const ALL = '*';

    /**
     * @var string
     */
    private $schemaKey;

    /**
     * @var Type[]
     */
    private $types = [];

    /**
     * @var Field[]
     */
    private $queries = [];

    /**
     * @var ModelType[]
     */
    private $models = [];

    /**
     * @var InterfaceType[]
     */
    private $interfaces = [];

    /**
     * @var Enum[]
     */
    private $enums = [];

    /**
     * @var Query[]
     */
    private $queryFields = [];

    /**
     * @var Mutation[]
     */
    private $mutationFields = [];

    /**
     * @var array
     */
    private $modelDependencies = [];

    /**
     * Schema constructor.
     * @param string $schemaKey
     */
    public function __construct(string $schemaKey)
    {
        $this->schemaKey = $schemaKey;
    }

    /**
     * @param array $schemaConfig
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $schemaConfig): Schema
    {
        Benchmark::start('schema-config');
        $types = $schemaConfig[self::TYPES] ?? [];
        $queries = $schemaConfig[self::QUERIES] ?? [];
        $mutations = $schemaConfig[self::MUTATIONS] ?? [];
        $interfaces = $schemaConfig[self::INTERFACES] ?? [];
        $models = $schemaConfig[self::MODELS] ?? [];
        $enums = $schemaConfig[self::ENUMS] ?? [];

        static::assertValidConfig($types);
        foreach ($types as $typeName => $typeConfig) {
            static::assertValidName($typeName);
            $type = Type::create($typeName, $typeConfig);
            $this->addType($type);
        }

        static::assertValidConfig($queries);
        foreach ($queries as $queryName => $queryConfig) {
            $query = Query::create($queryName, $queryConfig);
            $this->queryFields[$query->getName()] = $query;
        }

        static::assertValidConfig($mutations);
        foreach ($mutations as $mutationName => $mutationConfig) {
            $mutation = Mutation::create($mutationName, $mutationConfig);
            $this->mutationFields[$mutation->getName()] = $mutation;
        }

        static::assertValidConfig($interfaces);
        foreach ($interfaces as $interfaceName => $interfaceConfig) {
            static::assertValidName($interfaceName);
            $interface = InterfaceType::create($interfaceName, $interfaceConfig);
            $this->interfaces[$interfaceName] = $interface;
        }

        static::assertValidConfig($models);
        foreach ($models as $modelName => $modelConfig) {
             $model = ModelType::create($modelName, $modelConfig);
             $this->addModel($model);
        }

        static::assertValidConfig($enums);
        foreach ($enums as $enumName => $enumConfig) {
            Schema::assertValidConfig($enumConfig, ['values', 'description']);
            $values = $enumConfig['values'] ?? null;
            Schema::invariant($values, 'No values passed to enum %s', $enumName);
            $description = $enumConfig['description'] ?? null;
            $enum = Enum::create($enumName, $enumConfig['values'], $description);
            $this->enums[$enumName] = $enum;
        }

        foreach ($this->modelDependencies as $modelClass) {
            $this->addModel(ModelType::create($modelClass));
        }
        Benchmark::start('schema-updates');
        $this->applySchemaUpdates($schemaConfig);
        Benchmark::end('schema-updates', 'Schema updates took %s ms');

        foreach ($this->models as $modelType) {
            $this->addType($modelType);
        }

        $queryType = Type::create(self::QUERY_TYPE, [
            'fields' => $this->queryFields,
        ]);
        $this->types[self::QUERY_TYPE] = $queryType;

        if (!empty($mutationFields)) {
            $mutationType = Type::create(self::MUTATION_TYPE, [
                'fields' => $this->mutationFields,
            ]);
            $this->types[self::MUTATION_TYPE] = $mutationType;
        }

        Benchmark::end('schema-config', 'Schema config took %s ms');
        return $this;
    }

    /**
     * @param array $schemaConfig
     * @throws SchemaBuilderException
     */
    private function applySchemaUpdates(array $schemaConfig): void
    {
        Benchmark::start('builders');
        $builders = $schemaConfig['builders'] ?? [];
        foreach ($builders as $builderClass) {
            static::invariant(
                is_subclass_of($builderClass, SchemaUpdater::class),
                'The schema builder %s is not an instance of %s',
                $builderClass,
                SchemaUpdater::class
            );
            $builderClass::updateSchema($this);
        }
        Benchmark::end('builders');

        // Create a map of all the lists we need to apply plugins to, and their
        // required plugin interface
        $allComponents = [
            'types' => TypePlugin::class,
            'models' => ModelTypePlugin::class,
            'queryFields' => QueryPlugin::class,
            'mutationFields' => MutationPlugin::class,
        ];

        $schemaUpdates = [];
        foreach($allComponents as $propertyName => $pluginInterface) {
            foreach ($this->$propertyName as $component) {
                /* @var Type|Field $component */
                foreach ($component->loadPlugins() as $data) {
                    list ($plugin) = $data;
                    if ($plugin instanceof SchemaUpdater) {
                        $schemaUpdates[get_class($plugin)] = get_class($plugin);
                    }
                }
            }
        }
        Benchmark::start('plugin-schema-update');
        /* @var SchemaUpdater $builder */
        foreach ($schemaUpdates as $class) {
            $class::updateSchema($this);
        }
        Benchmark::end('plugin-schema-update');

        foreach ($allComponents as $propertyName => $pluginInterface) {
            Benchmark::start($propertyName . '-plugins');
            foreach ($this->$propertyName as $component) {
                /* @var Type|Field $component */
                foreach ($component->loadPlugins() as $data) {
                    list ($plugin, $config) = $data;
                    /* @var QueryPlugin|MutationPlugin|TypePlugin|ModelType $plugin */
                    if ($plugin instanceof $pluginInterface) {
                        $plugin->apply($component, $this, $config);
                    }
                }
            }
            Benchmark::end($propertyName . '-plugins');
        }
    }

    /**
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function loadFromConfig(): Schema
    {
        $schemas = $this->config()->get('schemas');
        static::invariant($schemas, 'There are no schemas defined in the config');
        $schema = $schemas[$this->schemaKey] ?? null;
        static::invariant($schema, 'Schema "%s" is not configured', $this->schemaKey);
        $globals = $schemas[self::ALL] ?? [];
        $allConfig = array_merge_recursive($globals, $schema);
        $this->applyConfig($allConfig);

        return $this;
    }

    public function persistSchema(): void
    {
        $this->validate();
        $schemaFileName = ASSETS_PATH . '/schema.php';
        $data = new ArrayData([
            'TypesClassName' => EncodedType::TYPE_CLASS_NAME,
            'Hash' => $this->getHash(),
            'Types' => ArrayList::create(array_values($this->types)),
            'Queries' => ArrayList::create(array_values($this->queries)),
            'Mutations' => ArrayList::create(array_values($this->mutationFields)),
            'Interfaces' => ArrayList::create(array_values($this->interfaces)),
            'Enums' => ArrayList::create(array_values($this->enums)),
            'QueryType' => self::QUERY_TYPE,
            'MutationType' => self::MUTATION_TYPE,
        ]);
        Benchmark::start('render');
        $code = $data->renderWith(__NAMESPACE__ . '\\GraphQLTypeRegistry');
        Benchmark::end('render', 'Code generation took %s ms');
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $code);
        $php = "<?php\n\n{$code}";
        file_put_contents($schemaFileName, $php);
    }

    public function getSchema(): GraphQLSchema
    {
        $schemaFileName = ASSETS_PATH . '/schema.php';
        require_once($schemaFileName);
        $hash = $this->getHash();
        $namespace = 'SilverStripe\\GraphQL\\Schema\\Generated\\Schema_' . $hash;
        $registry = $namespace . '\\Types';
        $hasMutations = method_exists($registry, self::MUTATION_TYPE);
        $schemaConfig = new SchemaConfig();
        $callback = call_user_func([$registry, self::QUERY_TYPE]);
        $schemaConfig->setQuery($callback());
        if ($hasMutations) {
            $callback = call_user_func([$registry, self::MUTATION_TYPE]);
            $schemaConfig->setMutation($callback());
        }
        return new GraphQLSchema($schemaConfig);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        $validators = array_merge(
            $this->types,
            $this->queries,
            $this->mutationFields,
            $this->enums,
            $this->interfaces
        );
        /* @var SchemaValidator $validator */
        foreach ($validators as $validator) {
            $validator->validate();
        }
    }

    /**
     * @param Type $type
     * @param callable|null $callback
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function addType(Type $type, ?callable $callback = null): Schema
    {
        $existing = $this->types[$type->getName()] ?? null;
        $typeObj = $existing ? $existing->mergeWith($type) : $type;
        $this->types[$type->getName()] = $typeObj;
        if ($callback) {
            $callback($typeObj);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return Type|null
     */
    public function getType(string $name): ?Type
    {
        return $this->types[$name] ?? null;
    }

    /**
     * @param string $name
     * @return Type
     * @throws SchemaBuilderException
     */
    public function findOrMakeType(string $name): Type
    {
        $existing = $this->getType($name);
        if ($existing) {
            return $existing;
        }
        $this->addType(Type::create($name));

        return $this->getType($name);
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param Enum $enum
     * @return $this
     */
    public function addEnum(Enum $enum): self
    {
        $this->enums[$enum->getName()] = $enum;

        return $this;
    }

    /**
     * @return Enum[]
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    /**
     * @param $name
     * @return Enum|null
     */
    public function getEnum($name): ?Enum
    {
        return $this->enums[$name] ?? null;
    }

    /**
     * @param ModelType $modelType
     * @param callable|null $callback
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function addModel(ModelType $modelType, ?callable $callback = null): Schema
    {
        $existing = $this->models[$modelType->getName()] ?? null;
        $typeObj = $existing
            ? $existing->mergeWith($modelType)
            : $modelType;
        $this->models[$modelType->getName()] = $typeObj;

        foreach ($modelType->getExtraTypes() as $type) {
            if ($type instanceof ModelType) {
                $this->addModel($type);
            } else {
                $this->addType($type);
            }
        }

        foreach ($modelType->getOperations() as $operationType) {
            Schema::invariant(
                $operationType instanceof ModelOperation,
                'Invalid operation defined on %s. Must implement %s',
                $modelType->getName(),
                ModelOperation::class
            );
            if ($operationType instanceof ModelQuery) {
                $this->queryFields[$operationType->getName()] = $operationType;
            } else if ($operationType instanceof ModelMutation) {
                $this->mutationFields[$operationType->getName()] = $operationType;
            }
        }
        if ($callback) {
            $callback($typeObj);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return ModelType|null
     */
    public function getModel(string $name): ?ModelType
    {
        return $this->models[$name] ?? null;
    }

    /**
     * @param string $class
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function findOrMakeModel(string $class): ModelType
    {
        $newModel = ModelType::create($class);
        $name = $newModel->getName();
        $existing = $this->getModel($name);
        if ($existing) {
            return $existing;
        }
        $this->addModel($newModel);

        return $this->getModel($name);
    }


    /**
     * @return ModelType[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    private function getHash(): string
    {
        return md5('UncleCheese');
    }

    /**
     * @return array
     */
    public static function getInternalTypes(): array
    {
        return ['String', 'Boolean', 'Int', 'Float', 'ID'];
    }

    /**
     * Pluralise a name
     *
     * @param string $typeName
     * @return string
     */
    public static function pluralise($typeName): string
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }

    /**
     * @param array $config
     * @param array $allowedKeys
     * @param array $requiredKeys
     * @throws SchemaBuilderException
     */
    public static function assertValidConfig(array $config, $allowedKeys = [], $requiredKeys = []): void
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

        if (!empty($requiredKeys)) {
            $missingKeys = array_diff($requiredKeys, array_keys($config));
            static::invariant(
                empty($missingKeys),
                'Config is missing required keys: %s',
                implode(',', $missingKeys)
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
