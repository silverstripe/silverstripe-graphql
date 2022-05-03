<?php

namespace SilverStripe\GraphQL\Schema;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Config\Configuration;
use SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader;
use SilverStripe\GraphQL\Schema\BulkLoader\BulkLoaderSet;
use SilverStripe\GraphQL\Schema\BulkLoader\Registry;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
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
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Interfaces\TypePlugin;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
use SilverStripe\GraphQL\Schema\Type\Scalar;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\TypeReference;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\ORM\ArrayLib;
use Exception;
use SilverStripe\Core\Injector\Injector;
use TypeError;

/**
 * The main Schema definition. A docking station for all type, model and interface abstractions.
 * Applies plugins, validates, and persists to code.
 *
 * Use {@link SchemaBuilder} to create functional instances of this type
 * based on YAML configuration.
 */
class Schema implements ConfigurationApplier
{
    use Injectable;
    use Configurable;

    const SCHEMA_CONFIG = 'config';
    const SOURCE = 'src';
    const TYPES = 'types';
    const QUERIES = 'queries';
    const MUTATIONS = 'mutations';
    const BULK_LOAD = 'bulkLoad';
    const MODELS = 'models';
    const INTERFACES = 'interfaces';
    const UNIONS = 'unions';
    const ENUMS = 'enums';
    const SCALARS = 'scalars';
    const QUERY_TYPE = 'Query';
    const MUTATION_TYPE = 'Mutation';
    const ALL = '*';

    /**
     * @config
     * @var callable
     */
    private static $pluraliser = [self::class, 'pluraliser'];

    private static bool $verbose = false;

    private LoggerInterface $logger;

    private string $schemaKey;

    /**
     * @var Type[]
     */
    private array $types = [];

    /**
     * @var ModelType[]
     */
    private array $models = [];

    /**
     * @var InterfaceType[]
     */
    private array $interfaces = [];

    /**
     * @var UnionType[]
     */
    private array $unions = [];

    /**
     * @var Enum[]
     */
    private array $enums = [];

    /**
     * @var Scalar[]
     */
    private array $scalars = [];

    private Type $queryType;

    private Type $mutationType;

    private SchemaConfig $schemaConfig;

    private Configuration $state;

    public function __construct(string $schemaKey, ?SchemaConfig $schemaConfig = null)
    {
        $this->schemaKey = $schemaKey;
        $this->queryType = Type::create(self::QUERY_TYPE);
        $this->mutationType = Type::create(self::MUTATION_TYPE);

        $this->schemaConfig = $schemaConfig ?: SchemaConfig::create();
        $this->state = Configuration::create();
        $this->logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
    }

    /**
     * Converts a configuration array to instance state.
     * This is only needed for deeper customisations,
     * since the configuration is auto-discovered and applied
     * through the {@link SchemaBuilder::boot()} step.
     *
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $schemaConfig): Schema
    {
        if (empty($schemaConfig)) {
            return $this;
        }

        $validConfigKeys = [
            self::TYPES,
            self::QUERIES,
            self::MUTATIONS,
            self::INTERFACES,
            self::UNIONS,
            self::BULK_LOAD,
            self::MODELS,
            self::ENUMS,
            self::SCALARS,
            self::SCHEMA_CONFIG,
            'execute',
            self::SOURCE,
        ];
        static::assertValidConfig($schemaConfig, $validConfigKeys);

        $types = $schemaConfig[self::TYPES] ?? [];
        $queries = $schemaConfig[self::QUERIES] ?? [];
        $mutations = $schemaConfig[self::MUTATIONS] ?? [];
        $interfaces = $schemaConfig[self::INTERFACES] ?? [];
        $unions = $schemaConfig[self::UNIONS] ?? [];
        $bulkLoad = $schemaConfig[self::BULK_LOAD] ?? [];
        $models = $schemaConfig[self::MODELS] ?? [];
        $enums = $schemaConfig[self::ENUMS] ?? [];
        $scalars = $schemaConfig[self::SCALARS] ?? [];
        $config = $schemaConfig[self::SCHEMA_CONFIG] ?? [];


        $this->getConfig()->apply($config);

        static::assertValidConfig($types);
        foreach ($types as $typeName => $typeConfig) {
            static::assertValidName($typeName);
            $input = $typeConfig['input'] ?? false;
            unset($typeConfig['input']);
            $type = $input
                ? InputType::create($typeName, $typeConfig)
                : Type::create($typeName, $typeConfig);
            $this->addType($type);
        }

        static::assertValidConfig($queries);
        foreach ($queries as $queryName => $queryConfig) {
            $this->addQuery(Query::create($queryName, $queryConfig));
        }

        static::assertValidConfig($mutations);
        foreach ($mutations as $mutationName => $mutationConfig) {
            $this->addMutation(Mutation::create($mutationName, $mutationConfig));
        }

        static::assertValidConfig($interfaces);
        foreach ($interfaces as $interfaceName => $interfaceConfig) {
            static::assertValidName($interfaceName);
            $interface = InterfaceType::create($interfaceName, $interfaceConfig);
            $this->addInterface($interface);
        }

        static::assertValidConfig($unions);
        foreach ($unions as $unionName => $unionConfig) {
            static::assertValidName($unionName);
            $union = UnionType::create($unionName, $unionConfig);
            $this->addUnion($union);
        }

        if (!empty($bulkLoad)) {
            static::assertValidConfig($bulkLoad);

            foreach ($bulkLoad as $name => $loaderData) {
                if ($loaderData === false) {
                    continue;
                }
                static::assertValidConfig($loaderData, ['load', 'apply'], ['load', 'apply']);

                $this->logger->info(sprintf('Processing bulk load "%s"', $name));

                $set = $this->getDefaultBulkLoaderSet()
                    ->applyConfig($loaderData['load']);
                $this->applyBulkLoaders($set, $loaderData['apply']);
            }
        }
        static::assertValidConfig($models);
        foreach ($models as $modelName => $modelConfig) {
             $model = $this->createModel($modelName, $modelConfig);
             Schema::invariant(
                 $model,
                 'No model found for "%s". Maybe the class does not exist?',
                 $modelName
             );
             $this->addModel($model);
        }

        static::assertValidConfig($enums);
        foreach ($enums as $enumName => $enumConfig) {
            Schema::assertValidConfig($enumConfig, ['values', 'description']);
            $values = $enumConfig['values'] ?? null;
            Schema::invariant($values, 'No values passed to enum %s', $enumName);
            $description = $enumConfig['description'] ?? null;
            $enum = Enum::create($enumName, $enumConfig['values'], $description);
            $this->addEnum($enum);
        }

        static::assertValidConfig($scalars);
        foreach ($scalars as $scalarName => $scalarConfig) {
            $scalar = Scalar::create($scalarName, $scalarConfig);
            $this->addScalar($scalar);
        }

        $this->applyProceduralUpdates($config['execute'] ?? []);

        return $this;
    }

    /**
     * Fills in values for types with lazy definitions, e.g. type expressed as class name
     *
     * @throws SchemaBuilderException
     */
    private function processTypes(): void
    {
        $types = array_merge(
            $this->getTypes(),
            [$this->queryType, $this->mutationType]
        );
        foreach ($types as $type) {
            foreach ($type->getFields() as $fieldObj) {
                $modelTypeDef = $fieldObj->getTypeAsModel();
                if (!$modelTypeDef || $fieldObj->getType()) {
                    continue;
                }
                $safeModelTypeDef = str_replace('\\', '__', $modelTypeDef ?? '');
                $safeModelTypeRef = TypeReference::create($safeModelTypeDef);
                [$safeNamedClass, $path] = $safeModelTypeRef->getTypeName();
                $namedClass = str_replace('__', '\\', $safeNamedClass ?? '');
                $model = $this->getConfig()->createModel($namedClass);
                Schema::invariant(
                    $model,
                    'No model found for %s',
                    $namedClass
                );

                $typeName = $model->getTypeName();
                $newTypeRef = TypeReference::createFromPath($typeName, $path);
                $fieldObj->setType($newTypeRef->getRawType());
            }
        }
    }

    /**
     * Apply resolver discovery
     * @throws SchemaBuilderException
     */
    private function processFields(): void
    {
        $types = array_merge(
            $this->getTypes(),
            $this->getInterfaces()
        );
        foreach ($types as $type) {
            foreach ($type->getFields() as $fieldObj) {
                if (!$fieldObj->getResolver()) {
                    $discoveredResolver = $this->getConfig()->discoverResolver($type, $fieldObj);
                    Schema::invariant(
                        $discoveredResolver,
                        'Could not discover a resolver for field %s on type %s',
                        $fieldObj->getName(),
                        $type->getName()
                    );
                    $fieldObj->setResolver($discoveredResolver);
                }
            }
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    private function processModels(): void
    {
        foreach ($this->getModels() as $modelType) {
            // Apply default plugins
            $model = $modelType->getModel();
            $plugins = $model->getModelConfiguration()->get('plugins', []);
            $modelType->setDefaultPlugins($plugins);
            $modelType->buildOperations();

            foreach ($modelType->getOperations() as $operationName => $operationType) {
                Schema::invariant(
                    $operationType instanceof ModelOperation,
                    'Invalid operation defined on %s. Must implement %s',
                    $modelType->getName(),
                    ModelOperation::class
                );

                if ($operationType instanceof ModelQuery) {
                    $this->addQuery($operationType);
                } else {
                    if ($operationType instanceof ModelMutation) {
                        $this->addMutation($operationType);
                    }
                }
            }
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    private function applyProceduralUpdates(array $builders): void
    {
        foreach ($builders as $builderClass) {
            static::invariant(
                is_subclass_of($builderClass, SchemaUpdater::class),
                'The schema builder %s is not an instance of %s',
                $builderClass,
                SchemaUpdater::class
            );
            $builderClass::updateSchema($this);
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    private function processConfig(): void
    {
        $typeMapping = [];
        $fieldMapping = [];
        foreach ($this->getModels() as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if (!isset($typeMapping[$class])) {
                $typeMapping[$class] = $modelType->getName();
            }
            $fields = [];
            /* @var ModelField $modelField */
            foreach ($modelType->getFields() as $modelField) {
                $relatedModel = $modelField->getModelType();
                $model = $relatedModel ?: $modelType;
                $fields[$modelField->getName()] = [$model->getName(), $modelField->getPropertyName()];
            }
            $fieldMapping[$modelType->getName()] = $fields;
        }

        $this->getConfig()
            ->setTypeMapping($typeMapping)
            ->setFieldMapping($fieldMapping);
    }

    /**
     * @throws SchemaBuilderException
     * @throws Exception
     */
    private function applySchemaUpdates(): void
    {
        // These updates mutate the state of the schema, so each iteration
        // needs to retrieve the components dynamically.
        $this->applySchemaUpdatesFromSet($this->getTypeComponents());
        $this->applyComponentUpdatesFromSet($this->getTypeComponents());

        $this->applySchemaUpdatesFromSet($this->getFieldComponents());
        $this->applyComponentUpdatesFromSet($this->getFieldComponents());
    }

    private function applySchemaUpdatesFromSet(array $componentSet): void
    {
        $schemaUpdates = [];
        foreach ($componentSet as $components) {
            /* @var SchemaComponent $component */
            $schemaUpdates = array_merge($schemaUpdates, $this->collectSchemaUpdaters($components));
        }

        /* @var SchemaUpdater $builder */
        foreach ($schemaUpdates as $spec) {
            list ($class) = $spec;
            $class::updateSchema($this);
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    private function applyComponentUpdatesFromSet(array $componentSet): void
    {
        foreach ($componentSet as $name => $components) {
            /* @var SchemaComponent $component */
            foreach ($components as $component) {
                $this->applyComponentPlugins($component, $name);
            }
        }
    }

    private function getTypeComponents(): array
    {
        return [
            'types' => array_merge($this->types, $this->interfaces),
            'models' => $this->models,
            'queries' => $this->queryType->getFields(),
            'mutations' => $this->mutationType->getFields(),
        ];
    }

    private function getFieldComponents(): array
    {
        $allTypeFields = [];
        $allModelFields = [];
        foreach ($this->types as $type) {
            if ($type->getIsInput()) {
                continue;
            }
            $pluggedFields = array_filter($type->getFields() ?? [], function (Field $field) use ($type) {
                return !empty($field->getPlugins());
            });
            $allTypeFields = array_merge($allTypeFields, array_values($pluggedFields ?? []));
        }
        foreach ($this->models as $model) {
            $pluggedFields = array_filter(array_values($model->getFields() ?? []), function (ModelField $field) {
                return !empty($field->getPlugins());
            });
            $allModelFields = array_merge($allModelFields, array_values($pluggedFields ?? []));
        }

        return [
            'fields' => $allTypeFields,
            'modelFields' => $allModelFields,
        ];
    }

    /**
     * @throws SchemaBuilderException
     */
    private function applyComponentPlugins(SchemaComponent $component, string $name): void
    {
        foreach ($component->loadPlugins() as $data) {
            /* @var QueryPlugin|MutationPlugin|TypePlugin|ModelTypePlugin $plugin */
            list ($plugin, $config) = $data;

            // Duck programming here just because there is such an exhaustive list of possible
            // interfaces, and they can't have a common ancestor until PHP 7.4 allows it.
            // https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters
            // ideally, this should be `instanceof PluginInterface` and PluginInterface should have
            // apply(SchemaComponent)
            if (!method_exists($plugin, 'apply')) {
                continue;
            }

            try {
                $plugin->apply($component, $this, $config);
            } catch (SchemaBuilderException $e) {
                throw new SchemaBuilderException(sprintf(
                    'Failed to apply plugin %s to %s. Got error "%s"',
                    get_class($plugin),
                    $component->getName(),
                    $e->getMessage()
                ));
            } catch (TypeError $e) {
                throw new SchemaBuilderException(sprintf(
                    'Error applying plugin %s to component "%s": %s',
                    $plugin->getIdentifier(),
                    $component->getName(),
                    $e->getMessage()
                ));
            }
        }
    }

    private function collectSchemaUpdaters(array $components): array
    {
        $schemaUpdates = [];
        foreach ($components as $component) {
            foreach ($component->loadPlugins() as $data) {
                list ($plugin, $config) = $data;
                if ($plugin instanceof SchemaUpdater) {
                    $schemaUpdates[get_class($plugin)] = [get_class($plugin), $config];
                }
            }
        }

        return $schemaUpdates;
    }

    /**
     * Process any lazy defined properties (types and modules),
     * and execute plugins. This step is automatically taken during {@link save()},
     * but can be useful to execute separately in order to determine if the schema
     * is valid before saving.
     *
     * @return void
     */
    private function process(): void
    {
        // Fill in lazily defined type properties, e.g. fields with a classname as a type
        $this->processTypes();
        // Create operations, add default plugins
        $this->processModels();
        // Execute plugins
        $this->applySchemaUpdates();

        // Map types and fields
        $this->processConfig();

        // Models have expressed all they can now. They can graduate to actual types.
        foreach ($this->models as $modelType) {
            $this->addType($modelType);
        }

        $this->types[self::QUERY_TYPE] = $this->queryType;
        if ($this->mutationType->exists()) {
            $this->types[self::MUTATION_TYPE] = $this->mutationType;
        }

        // Resolver discovery
        $this->processFields();
    }

    /**
     * Creates a readonly object that can be used by a storage service.
     * Processes all of the types, fields, models, etc to end up with a coherent,
     * schema that can be validated and stored.
     */
    public function createStoreableSchema(): StorableSchema
    {
        $this->process();
        $schema = StorableSchema::create(
            [
                self::TYPES => $this->getTypes(),
                self::ENUMS => $this->getEnums(),
                self::INTERFACES => $this->getInterfaces(),
                self::UNIONS => $this->getUnions(),
                self::SCALARS => $this->getScalars()
            ],
            $this->getConfig()
        );

        return $schema;
    }

    public function exists(): bool
    {
        return !empty($this->types) && $this->queryType->exists();
    }

    public function getSchemaKey(): string
    {
        return $this->schemaKey;
    }

    public function getConfig(): SchemaConfig
    {
        return $this->schemaConfig;
    }

    public function getState(): Configuration
    {
        return $this->state;
    }

    public function addQuery(Field $query): self
    {
        $this->queryType->addField($query->getName(), $query);

        return $this;
    }

    public function addMutation(Field $mutation): self
    {
        $this->mutationType->addField($mutation->getName(), $mutation);

        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function addType(Type $type, ?callable $callback = null): self
    {
        $existing = $this->types[$type->getName()] ?? null;
        $typeObj = $existing ? $existing->mergeWith($type) : $type;
        $this->types[$type->getName()] = $typeObj;
        if ($callback) {
            $callback($typeObj);
        }
        return $this;
    }

    public function removeType(string $type): self
    {
        unset($this->types[$type]);

        return $this;
    }

    public function getType(string $name): ?Type
    {
        return $this->types[$name] ?? null;
    }

    /**
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
     * Given a type name, try to resolve it to any model-implementing component
     */
    public function getCanonicalType(string $typeName): ?Type
    {
        $type = $this->getTypeOrModel($typeName);
        if ($type) {
            return $type;
        }

        $union = $this->getUnion($typeName);
        if ($union instanceof ModelUnionType) {
            return $union->getCanonicalModel();
        }

        $interface = $this->getInterface($typeName);
        if ($interface instanceof ModelInterfaceType) {
            return $interface->getCanonicalModel();
        }

        return null;
    }

    /**
     * Gets all the models that were generated from a given ancestor, e.g. DataObject
     * @param string $class
     * @return ModelType[]
     */
    public function getModelTypesFromClass(string $class): array
    {
        return array_filter($this->getModels() ?? [], function (ModelType $modelType) use ($class) {
            $source = $modelType->getModel()->getSourceClass();
            return $source === $class || is_subclass_of($source, $class ?? '');
        });
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getQueryType(): Type
    {
        return $this->queryType;
    }

    public function getMutationType(): Type
    {
        return $this->mutationType;
    }

    public function getTypeOrModel(string $name): ?Type
    {
        return $this->getType($name) ?: $this->getModel($name);
    }

    public function addEnum(Enum $enum): self
    {
        $this->enums[$enum->getName()] = $enum;
        return $this;
    }

    public function removeEnum(string $name): self
    {
        unset($this->enums[$name]);
        return $this;
    }

    /**
     * @return Enum[]
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    public function getEnum(string $name): ?Enum
    {
        return $this->enums[$name] ?? null;
    }

    public function getScalars(): array
    {
        return $this->scalars;
    }

    public function getScalar(string $name): ?Scalar
    {
        return $this->scalars[$name] ?? null;
    }

    public function addScalar(Scalar $scalar): self
    {
        $this->scalars[$scalar->getName()] = $scalar;
        return $this;
    }

    public function removeScalar(string $name): self
    {
        unset($this->scalars[$name]);
        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function addModel(ModelType $modelType, ?callable $callback = null): Schema
    {
        $existing = $this->models[$modelType->getName()] ?? null;
        if ($existing) {
            Schema::invariant(
                $existing->getModel()->getSourceClass() === $modelType->getModel()->getSourceClass(),
                'Name collision for model "%s". It is used for both %s and %s. Use the tyepMapping setting in your schema
            config to provide a custom name for the model.',
                $modelType->getName(),
                $existing->getModel()->getSourceClass(),
                $modelType->getModel()->getSourceClass()
            );
        }
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
        if ($callback) {
            $callback($typeObj);
        }

        return $this;
    }

    public function getModel(string $name): ?ModelType
    {
        return $this->models[$name] ?? null;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function addModelbyClassName(string $class, ?callable $callback = null): self
    {
        $model = $this->createModel($class);
        Schema::invariant(
            $model,
            sprintf(
                'Could not add class %s to schema. No model exists.',
                $class
            )
        );

        if ($callback) {
            $callback($model);
        }
        return $this->addModel($model);
    }

    public function removeModelByClassName(string $class): self
    {
        if ($model = $this->getModelByClassName($class)) {
            $this->removeModel($model->getName());
        }

        return $this;
    }

    public function removeModel(string $name): self
    {
        unset($this->models[$name]);
        return $this;
    }

    public function getModelByClassName(string $class): ?ModelType
    {
        foreach ($this->getModels() as $modelType) {
            if ($modelType->getModel()->getSourceClass() === $class) {
                return $modelType;
            }
        }

        return null;
    }

    /**
     * Some types must be eagerly loaded into the schema if they cannot be discovered through introspection.
     * This may include types that do not appear in any queries.
     * @throws SchemaBuilderException
     */
    public function eagerLoad(string $name): self
    {
        $this->getConfig()->set("eagerLoadTypes.$name", $name);
        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function lazyLoad(string $name): self
    {
        $this->getConfig()->unset("eagerLoadTypes.$name");
        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function createModel(string $class, array $config = []): ?ModelType
    {
        $model = $this->getConfig()->createModel($class);
        if (!$model) {
            return null;
        }

        return ModelType::create($model, $config);
    }

    /**
     * @return ModelType[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function findOrMakeModel(string $class): ModelType
    {
        $newModel = $this->createModel($class);
        $name = $newModel->getName();
        $existing = $this->getModel($name);
        if ($existing) {
            return $existing;
        }
        $this->addModel($newModel);

        return $this->getModel($name);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function addInterface(InterfaceType $type, ?callable $callback = null): self
    {
        $existing = $this->interfaces[$type->getName()] ?? null;
        $typeObj = $existing ? $existing->mergeWith($type) : $type;
        $this->interfaces[$type->getName()] = $typeObj;
        if ($callback) {
            $callback($typeObj);
        }
        return $this;
    }

    public function removeInterface(string $name): self
    {
        unset($this->interfaces[$name]);

        return $this;
    }

    public function getInterface(string $name): ?InterfaceType
    {
        return $this->interfaces[$name] ?? null;
    }

    /**
     * @return InterfaceType[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getImplementorsOf(string $interfaceName): array
    {
        $search = array_merge($this->getTypes(), $this->getModels());
        return array_filter($search ?? [], function (Type $type) use ($interfaceName) {
            return $type->implements($interfaceName);
        });
    }

    public function addUnion(UnionType $union, ?callable $callback = null): self
    {
        $existing = $this->unions[$union->getName()] ?? null;
        $typeObj = $existing ? $existing->mergeWith($union) : $union;
        $this->unions[$union->getName()] = $typeObj;
        if ($callback) {
            $callback($typeObj);
        }
        return $this;
    }

    public function removeUnion(string $name): self
    {
        unset($this->unions[$name]);
        return $this;
    }

    public function getUnion(string $name): ?UnionType
    {
        return $this->unions[$name] ?? null;
    }

    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function applyBulkLoader(AbstractBulkLoader $loader, array $modelConfig): self
    {
        $set = $this->getDefaultBulkLoaderSet();
        $set->addLoader($loader);

        return $this->applyBulkLoaders($set, $modelConfig);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function applyBulkLoaders(BulkLoaderSet $loaders, array $modelConfig): self
    {
        $collection = $loaders->process();
        $count = 0;
        foreach ($collection->getClasses() as $includedClass) {
            $modelType = $this->createModel($includedClass, $modelConfig);
            if (!$modelType) {
                continue;
            }
            $this->logger->debug("Bulk loaded $includedClass");
            $count++;
            $this->addModel($modelType);
        }

        $this->logger->info("Bulk loaded $count classes");

        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    private function getDefaultBulkLoaderSet(): BulkLoaderSet
    {
        $loaders = [];
        $default = $this->getConfig()->get('defaultBulkLoad');
        if ($default && is_array($default)) {
            foreach ($default as $id => $config) {
                /* @var AbstractBulkLoader $defaultLoader */
                $defaultLoader = Registry::inst()->getByID($id);
                static::invariant($defaultLoader, 'Default loader %s not found', $id);
                $loaders[] = $defaultLoader->applyConfig($config);
            }
        }

        return BulkLoaderSet::create($loaders);
    }

    /**
     * @return string[]
     */
    public static function getInternalTypes(): array
    {
        return ['String', 'Boolean', 'Int', 'Float', 'ID'];
    }

    public static function isInternalType(string $type): bool
    {
        return in_array($type, static::getInternalTypes() ?? []);
    }

    /**
     * Pluralise a name
     *
     * @throws SchemaBuilderException
     */
    public function pluralise(string $typeName): string
    {
        $callable = $this->getConfig()->getPluraliser();
        Schema::invariant(
            is_callable($callable),
            'Schema does not have a valid callable "pluraliser" property set in its config'
        );

        return call_user_func_array($callable, [$typeName]);
    }

    /**
     * @throws SchemaBuilderException
     */
    public static function assertValidConfig(array $config, $allowedKeys = [], $requiredKeys = []): void
    {
        static::invariant(
            empty($config) || ArrayLib::is_associative($config),
            "%s configurations must be key value pairs of names to configurations.
            Did you include an indexed array in your config?

            Context: %s",
            static::class,
            json_encode($config)
        );

        if (!empty($allowedKeys)) {
            $invalidKeys = array_diff(array_keys($config ?? []), $allowedKeys);
            static::invariant(
                empty($invalidKeys),
                "Config contains invalid keys: %s. Allowed keys are %s.\n\nContext: %s",
                implode(',', $invalidKeys),
                implode(',', $allowedKeys),
                json_encode($config)
            );
        }

        if (!empty($requiredKeys)) {
            $missingKeys = array_diff($requiredKeys ?? [], array_keys($config ?? []));
            static::invariant(
                empty($missingKeys),
                "Config is missing required keys: %s.\n\nContext: %s",
                implode(',', $missingKeys),
                json_encode($config)
            );
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    public static function assertValidName(?string $name): void
    {
        static::invariant(
            preg_match(' /[_A-Za-z][_0-9A-Za-z]*/', $name ?? ''),
            'Invalid name: %s. Names must only use underscores and alphanumeric characters, and cannot
          begin with a number.',
            $name
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
            $message = count($params ?? []) > 0 ? sprintf($message, ...$params) : $message;
            throw new SchemaBuilderException($message);
        }
    }
}
