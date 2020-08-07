<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelAware;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultPluginProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ExtraTypeProvider;
use SilverStripe\GraphQL\Schema\Interfaces\InputTypeProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ModelBlacklist;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\OperationProvider;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Registry\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\Schema;

class ModelType extends Type implements ExtraTypeProvider
{
    use ModelAware;

    /**
     * @var string
     */
    private $sourceClass;

    /**
     * @var array
     */
    private $operationCreators = [];

    /**
     * @var Type[]
     */
    private $extraTypes = [];

    /**
     * @var array
     */
    private $blacklistedFields = [];


    /**
     * ModelType constructor.
     * @param string $class
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $class, array $config = [])
    {
        /* @var SchemaModelCreatorRegistry $registry */
        $registry = Injector::inst()->get(SchemaModelCreatorRegistry::class);
        $model = $registry->getModel($class);
        Schema::invariant($model, 'No model found for class %s', $class);

        $this->setModel($model);
        $this->setSourceClass($class);

        $type = $this->getModel()->getTypeName();
        Schema::invariant(
            $type,
            'Could not determine type for model %s',
            $this->getSourceClass()
        );

        /* @var SchemaModelInterface&ModelBlacklist $model */
        $this->blacklistedFields = $model instanceof ModelBlacklist ?
            array_map('strtolower', $model->getBlacklistedFields()) :
            [];

        if ($model instanceof DefaultPluginProvider) {
            $plugins = $config['plugins'] ?? [];
            $config['plugins'] = array_merge($plugins, $model->getDefaultPlugins());
        }

        parent::__construct($type);

        $this->applyConfig($config);
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, ['fields', 'operations', 'plugins']);

        $fieldConfig = $config['fields'] ?? [];
        if ($fieldConfig === Schema::ALL) {
            $this->addAllFields();
        } else {
            $fields = array_merge($this->getBaseFields(), $fieldConfig);
            Schema::assertValidConfig($fields);

            foreach ($fields as $fieldName => $data) {
                if ($data === false) {
                    continue;
                }
                $this->addField($fieldName, $data);
            }
        }

        $operations = $config['operations'] ?? null;
        if ($operations) {
            if ($operations === Schema::ALL) {
                $this->addAllOperations();
            } else {
                $this->applyOperationsConfig($operations);
            }
        }

        if (isset($config['plugins'])) {
            $this->setPlugins($config['plugins']);
        }
    }

    /**
     * @param string $fieldName
     * @param array|string|Field $fieldConfig
     * @param callable|null $callback
     * @return Type
     * @throws SchemaBuilderException
     */
    public function addField(string $fieldName, $fieldConfig, ?callable $callback = null): Type
    {
        if (!$fieldConfig instanceof Field) {
            $config = is_string($fieldConfig) ? ['type' => $fieldConfig] : $fieldConfig;
            $fieldObj = ModelField::create($fieldName, $config, $this->getModel());
        } else {
            $fieldObj = $fieldConfig;
        }

        Schema::invariant(
            !in_array(strtolower($fieldObj->getName()), $this->blacklistedFields),
            'Field %s is not allowed on %s',
            $fieldObj->getName(),
            $this->getModel()->getSourceClass()
        );

        $this->fields[$fieldObj->getName()] = $fieldObj;
        if ($callback) {
            call_user_func_array($callback, [$fieldObj]);
        }
        return $this;
    }

    /**
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function addAllFields(): self
    {
        /* @var SchemaModelInterface&DefaultFieldsProvider $model */
        $model = $this->getModel();
        $defaultFields = $model instanceof DefaultFieldsProvider ? $model->getDefaultFields() : [];
        foreach ($defaultFields as $fieldName => $fieldType) {
            $this->addField($fieldName, $fieldType);
        }
        $allFields = $this->getModel()->getAllFields();
        foreach ($allFields as $fieldName) {
            $this->addField($fieldName, $this->getModel()->getTypeForField($fieldName));
        }
        return $this;
    }

    /**
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function addAllOperations(): self
    {
        Schema::invariant(
            $this->getModel() instanceof OperationProvider,
            'Model for %s does not implement %s. No operations are allowed',
            $this->getName(),
            OperationProvider::class
        );
        /* @var SchemaModelInterface&OperationProvider $model */
        $model = $this->getModel();

        $operations = [];
        foreach ($model->getAllOperationIdentifiers() as $id) {
            $operations[$id] = true;
        }
        $this->applyOperationsConfig($operations);

        return $this;
    }


    /**
     * @param array $operations
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function applyOperationsConfig(array $operations): ModelType
    {
        Schema::assertValidConfig($operations);
        foreach ($operations as $operationName => $data) {
            if ($data === false) {
                continue;
            }

            Schema::invariant(
                is_array($data) || $data === true,
                'Operation data for %s must be a map of config or true for a generic implementation',
                $operationName
            );

            $config = ($data === true) ? [] : $data;
            $this->addOperation($operationName, $config);
        }

        return $this;
    }

    /**
     * @param string $fieldName
     * @return Field|null
     */
    public function getFieldByName(string $fieldName): ?Field
    {
        /* @var ModelField $fieldObj */
        foreach ($this->getFields() as $fieldObj) {
            if ($fieldObj->getName() === $fieldName) {
                return $fieldObj;
            }
        }
        return null;
    }

    /**
     * @param string $operationName
     * @param array $config
     * @return ModelType
     */
    public function addOperation(string $operationName, array $config = []): self
    {
        $this->operationCreators[$operationName] = $config;

        return $this;
    }

    /**
     * @param string $operationName
     * @return ModelType
     */
    public function removeOperation(string $operationName): self
    {
        unset($this->operationCreators[$operationName]);

        return $this;
    }

    /**
     * @param string $operationName
     * @param array $config
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function updateOperation(string $operationName, array $config = []): self
    {
        Schema::invariant(
            isset($this->operationCreators[$operationName]),
            'Cannot update nonexistent operation %s on %s',
            $operationName,
            $this->getName()
        );

        $this->operationCreators[$operationName] = array_merge(
            $this->operationCreators[$operationName],
            $config
        );

        return $this;
    }

    /**
     * @return ModelOperation[]
     * @throws SchemaBuilderException
     */
    public function getOperations(): array
    {
        $operations = [];
        foreach ($this->operationCreators as $operationName => $config) {
            $operationCreator = $this->getOperationCreator($operationName);
            $operations[] = $operationCreator->createOperation(
                $this->getModel(),
                $this->getName(),
                $config
            );
        }
        foreach ($this->getFields() as $field) {
            if (!$field instanceof ModelField) {
                continue;
            }
            if ($modelType = $field->getModelType()) {
                $operations = array_merge($operations, $modelType->getOperations());
            }
        }


        return array_filter($operations);
    }

    /**
     * @return Type[]
     * @throws SchemaBuilderException
     */
    public function getExtraTypes(): array
    {
        $extraTypes = $this->extraTypes;
        foreach ($this->operationCreators as $operationName => $config) {
            $operationCreator = $this->getOperationCreator($operationName);
            if (!$operationCreator instanceof InputTypeProvider) {
                continue;
            }
            $types = $operationCreator->provideInputTypes(
                $this->getModel(),
                $this->getName(),
                $config
            );
            foreach ($types as $type) {
                Schema::invariant(
                    $type instanceof InputType,
                    'Input types must be instances of %s on %s',
                    InputType::class,
                    $this->getName()
                );
                $extraTypes[] = $type;
            }
        }
        foreach ($this->getFields() as $field) {
            if (!$field instanceof ModelField) {
                continue;
            }
            if ($modelType = $field->getModelType()) {
                $extraTypes = array_merge($extraTypes, $modelType->getExtraTypes());
                $extraTypes[] = $modelType;
            }
        }
        if ($this->getModel() instanceof ExtraTypeProvider) {
            $extraTypes = array_merge($extraTypes, $this->getModel()->getExtraTypes());
        }

        return $extraTypes;
    }

    /**
     * @return string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    /**
     * @param string $sourceClass
     * @return ModelType
     */
    public function setSourceClass(string $sourceClass): ModelType
    {
        $this->sourceClass = $sourceClass;
        return $this;
    }

    /**
     * @return array
     */
    private function getBaseFields(): array
    {
        $model = $this->getModel();
        /* @var SchemaModelInterface&DefaultFieldsProvider&RequiredFieldsProvider $model */
        $defaultFields = $model instanceof DefaultFieldsProvider ? $model->getDefaultFields() : [];
        $requiredFields = $model instanceof RequiredFieldsProvider ? $model->getRequiredFields() : [];

        return array_merge($defaultFields, $requiredFields);
    }

    /**
     * @param string $operationName
     * @return OperationCreator
     * @throws SchemaBuilderException
     */
    private function getOperationCreator(string $operationName): OperationCreator
    {
        $operationCreator = $this->getModel()->getOperationCreatorByIdentifier($operationName);
        Schema::invariant($operationCreator, 'Invalid operation: %s', $operationName);

        return $operationCreator;
    }
}
