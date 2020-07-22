<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\Core\Injector\Injector;

class ModelAbstraction extends TypeAbstraction implements ExtraTypeProvider
{
    /**
     * @var SchemaModelInterface
     */
    private $model;

    /**
     * @var string
     */
    private $sourceClass;

    /**
     * @var array
     */
    private $operationCreators = [];

    /**
     * @var TypeAbstraction[]
     */
    private $extraTypes = [];


    /**
     * ModelAbstraction constructor.
     * @param string $class
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $class, array $config = [])
    {
        /* @var SchemaModelCreatorRegistry $registry */
        $registry = Injector::inst()->get(SchemaModelCreatorRegistry::class);
        $model = $registry->getModel($class);
        SchemaBuilder::invariant($model, 'No model found for class %s', $class);

        $this->setModel($model);
        $this->setSourceClass($class);

        $type = $this->getModel()->getTypeName();
        SchemaBuilder::invariant(
            $type,
            'Could not determine type for model %s',
            $this->getSourceClass()
        );

        parent::__construct($type);

        $this->applyConfig($config);
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        SchemaBuilder::assertValidConfig($config, ['fields', 'operations']);
        $model = $this->getModel();

        /* @var SchemaModelInterface&DefaultFieldsProvider&RequiredFieldsProvider $model */
        $defaultFields = $model instanceof DefaultFieldsProvider ? $model->getDefaultFields() : [];
        $requiredFields = $model instanceof RequiredFieldsProvider ? $model->getRequiredFields() : [];
        $fieldConfig = $config['fields'] ?? [];

        if ($fieldConfig === SchemaBuilder::ALL) {
            $fieldConfig = [];
            foreach ($this->getModel()->getAllFields() as $fieldName) {
                $fieldConfig[$fieldName] = $this->getModel()->getTypeForField($fieldName);
            }
        }

        $fields = array_merge($defaultFields, $requiredFields, $fieldConfig);
        $this->applyFieldsConfig($fields);
        $operations = $config['operations'] ?? null;
        if (!$operations) {
            return;
        }
        SchemaBuilder::invariant(
            $this->getModel() instanceof OperationProvider,
            'Model for %s does not implement %s. No operations are allowed',
            $this->getName(),
            OperationProvider::class
        );
        /* @var SchemaModelInterface&OperationProvider $model */
        $model = $this->getModel();
        if ($operations === SchemaBuilder::ALL) {
            $operations = [];
            foreach ($model->getAllOperationIdentifiers() as $id) {
                $operations[$id] = true;
            }
        }
        $this->applyOperationsConfig($operations);
    }

    /**
     * @param array $fields
     * @return ModelAbstraction
     * @throws SchemaBuilderException
     */
    public function applyFieldsConfig(array $fields): TypeAbstraction
    {
        SchemaBuilder::assertValidConfig($fields);
        $model = $this->getModel();
        /* @var SchemaModelInterface&ModelBlacklist $model */
        $blackListedFields = $model instanceof ModelBlacklist ?
            array_map('strtolower', $model->getBlacklistedFields()) :
            null;

        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            $abstract = ModelFieldAbstraction::create($fieldName, $data, $model);
            if ($blackListedFields) {
                SchemaBuilder::invariant(
                    !in_array(strtolower($abstract->getName()), $blackListedFields),
                    'Field %s is not allowed on %s',
                    $abstract->getName(),
                    $model->getSourceClass()
                );
            }

            $this->fields[$abstract->getName()] = $abstract;
        }

        return $this;
    }

    /**
     * @param array $operations
     * @return ModelAbstraction
     * @throws SchemaBuilderException
     */
    public function applyOperationsConfig(array $operations): ModelAbstraction
    {
        SchemaBuilder::assertValidConfig($operations);
        foreach ($operations as $operationName => $data) {
            if ($data === false) {
                continue;
            }

            SchemaBuilder::invariant(
                is_array($data) || $data === true,
                'Operation data for %s must be a map of config or true for a generic implementation',
                $operationName
            );

            $config = ($data === true) ? [] : $data;
            $this->operationCreators[$operationName] = $config;
        }

        return $this;
    }

    /**
     * @return FieldAbstraction[]
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
        foreach ($this->getFields() as $fieldAbstraction) {
            if (!$fieldAbstraction instanceof ModelFieldAbstraction) {
                continue;
            }
            if ($modelType = $fieldAbstraction->getModelType()) {
                $operations = array_merge($operations, $modelType->getOperations());
            }
        }


        return $operations;
    }

    /**
     * @return TypeAbstraction[]
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
                SchemaBuilder::invariant(
                    $type instanceof InputTypeAbstraction,
                    'Input types must be instances of %s on %s',
                    InputTypeAbstraction::class,
                    $this->getName()
                );
                $extraTypes[] = $type;
            }
        }
        foreach ($this->getFields() as $fieldAbstraction) {
            if (!$fieldAbstraction instanceof ModelFieldAbstraction) {
                continue;
            }
            if ($modelType = $fieldAbstraction->getModelType()) {
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
     * @return SchemaModelInterface
     */
    public function getModel(): SchemaModelInterface
    {
        return $this->model;
    }

    /**
     * @param SchemaModelInterface $model
     * @return ModelAbstraction
     */
    public function setModel(SchemaModelInterface $model): ModelAbstraction
    {
        $this->model = $model;
        return $this;
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
     * @return ModelAbstraction
     */
    public function setSourceClass(string $sourceClass): ModelAbstraction
    {
        $this->sourceClass = $sourceClass;
        return $this;
    }

    /**
     * @param TypeAbstraction[] $types
     */
    private function addExtraTypes(array $types)
    {
        $this->extraTypes = array_merge($this->extraTypes, $types);
    }

    /**
     * @param string $operationName
     * @return OperationCreator
     * @throws SchemaBuilderException
     */
    private function getOperationCreator(string $operationName): OperationCreator
    {
        $operationCreator = $this->getModel()->getOperationCreatorByIdentifier($operationName);
        SchemaBuilder::invariant($operationCreator, 'Invalid operation: %s', $operationName);

        return $operationCreator;
    }
}
