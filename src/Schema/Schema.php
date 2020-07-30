<?php

namespace SilverStripe\GraphQL\Schema;

use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Field\Mutation;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\MutationPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
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
     * @var Field[]
     */
    private $mutations = [];

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
     * @param array $schema
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $schema): Schema
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

        foreach ($this->models as $modelType) {
            $this->addType($modelType);
        }

        $schemaUpdates = [];
        foreach ($this->queryFields as $query) {
            foreach ($query->loadPlugins() as $data) {
                list ($plugin, $config) = $data;
                if ($plugin instanceof QueryPlugin) {
                    $plugin->apply($query, $this, $config);
                }
                if ($plugin instanceof SchemaUpdater) {
                    $schemaUpdates[get_class($plugin)] = $plugin;
                }
            }
        }
        foreach ($this->mutations as $mutation) {
            foreach ($mutation->loadPlugins() as $data) {
                list ($plugin, $config) = $data;
                if ($plugin instanceof MutationPlugin) {
                    $plugin->apply($mutation, $this, $config);
                }
                if ($plugin instanceof SchemaUpdater) {
                    $schemaUpdates[get_class($plugin)] = get_class($plugin);
                }
            }
        }
        /* @var SchemaUpdater $builder */
        foreach ($schemaUpdates as $class) {
            $class::updateSchemaOnce($this);
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

        return $this;
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
        $schemaFileName = BASE_PATH . '/schema.php';
        $data = new ArrayData([
            'TypesClassName' => EncodedType::TYPE_CLASS_NAME,
            'Hash' => $this->getHash(),
            'Types' => ArrayList::create(array_values($this->types)),
            'Queries' => ArrayList::create(array_values($this->queries)),
            'Mutations' => ArrayList::create(array_values($this->mutations)),
            'Interfaces' => ArrayList::create(array_values($this->interfaces)),
            'Enums' => ArrayList::create(array_values($this->enums)),
            'QueryType' => self::QUERY_TYPE,
            'MutationType' => self::MUTATION_TYPE,
        ]);
        $code = $data->renderWith(__NAMESPACE__ . '\\GraphQLTypeRegistry');
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $code);
        $php = "<?php\n\n{$code}";
        file_put_contents($schemaFileName, $php);
    }

    public function getSchema(): GraphQLSchema
    {
        $schemaFileName = BASE_PATH . '/schema.php';
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
            $this->mutations,
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
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function addType(Type $type): Schema
    {
        $existing = $this->types[$type->getName()] ?? null;
        $this->types[$type->getName()] = $existing ? $existing->mergeWith($type) : $type;

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
     * @param ModelType $modelType
     * @return Schema
     * @throws SchemaBuilderException
     */
    public function addModel(ModelType $modelType): Schema
    {
        $existing = $this->models[$modelType->getName()] ?? null;
        $this->models[$modelType->getName()] = $existing
            ? $existing->mergeWith($modelType)
            : $modelType;

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
