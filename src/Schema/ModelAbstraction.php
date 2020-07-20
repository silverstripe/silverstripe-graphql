<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\Core\Injector\Injector;

class ModelAbstraction extends TypeAbstraction
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
     * @var FieldAbstraction[]
     */
    private $operations = [];

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
        $defaultFields = $this->getModel()->getDefaultFields();
        $fieldConfig = $config['fields'] ?? [];

        if ($fieldConfig === SchemaBuilder::ALL) {
            $fieldConfig = [];
            foreach ($this->getModel()->getAllFields() as $fieldName) {
                $fieldConfig[$fieldName] = $this->getModel()->getTypeForField($fieldName);
            }
        }

        $fields = array_merge($defaultFields, $fieldConfig);
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
            $this->fields[$abstract->getName()] = $abstract;
            if ($modelType = $abstract->getModelType()) {
                $this->addExtraTypes($modelType->getExtraTypes());
                $this->addExtraTypes([$modelType]);
                $this->operations = array_merge($this->operations, $modelType->getOperations());
            }
            if ($blackListedFields) {
                SchemaBuilder::invariant(
                    !in_array(strtolower($abstract->getName()), $blackListedFields),
                    'Field %s is not allowed on %s',
                    $abstract->getName(),
                    $model->getSourceClass()
                );
            }
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
        /* @var SchemaModelInterface&OperationProvider $model */
        $model = $this->getModel();

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
            $operationCreator = $model->getOperationCreatorByIdentifier($operationName);

            SchemaBuilder::invariant($operationCreator, 'Invalid operation: %s', $operationName);

            $this->operations[] = $operationCreator->createOperation(
                $this->getModel(),
                $this->getName(),
                $config
            );

            if ($operationCreator instanceof InputTypeProvider) {
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
                }
                $this->addExtraTypes($types);
            }
        }

        return $this;
    }

    /**
     * @return FieldAbstraction[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return TypeAbstraction[]
     */
    public function getExtraTypes(): array
    {
        return $this->extraTypes;
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
}
