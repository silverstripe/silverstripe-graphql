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
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $class, ?array $config = null)
    {
        /* @var SchemaModelCreatorRegistry $registry */
        $registry = Injector::inst()->get(SchemaModelCreatorRegistry::class);
        $model = $registry->getModel($class);
        SchemaBuilder::invariant($model, 'No model found for class %s', $class);

        $this->setModel($model);
        $this->setSourceClass($class);

        $type = $config['type'] ?? null;
        SchemaBuilder::invariant(
            $type,
            'Model for %s has no type declared. All class names must specify types explicitly.',
            $this->getSourceClass()
        );

        parent::__construct($type);

        if ($config) {
            $this->applyConfig($config);
        }
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        SchemaBuilder::assertValidConfig($config, ['fields', 'type', 'operations']);
        $fields = $config['fields'] ?? [];
        $this->applyFieldsConfig($fields);
        $operations = $config['operations'] ?? [];
        $this->applyOperationsConfig($operations);
    }

    /**
     * @param array $fieldConfig
     * @return ModelAbstraction
     * @throws SchemaBuilderException
     */
    public function applyFieldsConfig(array $fieldConfig): TypeAbstraction
    {
        $defaultFields = $this->getModel()->getDefaultFields();
        $fields = array_merge($defaultFields, $fieldConfig);
        SchemaBuilder::assertValidConfig($fields);

        $blackListedFields = $this->getModel() instanceof ModelBlacklist ?
            array_map('strtolower', $this->getModel()->getBlacklistedFields()) :
            null;

        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            $abstract = ModelFieldAbstraction::create($fieldName, $data, $this->getModel());
            $this->fields[$abstract->getName()] = $abstract;
            if ($blackListedFields) {
                SchemaBuilder::invariant(
                    !in_array(strtolower($abstract->getName()), $blackListedFields),
                    'Field %s is not allowed on %s',
                    $abstract->getName(),
                    $this->getModel()->getSourceClass()
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
        SchemaBuilder::invariant(
            $this->getModel() instanceof OperationProvider,
            'Model for %s does not implement %s. No operations are allowed',
            $this->getName(),
            OperationProvider::class
        );
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
            $this->operations[] = $operationCreator->createOperation($this->getModel(), $this->getName(), $config);
            if ($operationCreator instanceof InputTypeProvider) {
                $types = $operationCreator->provideInputTypes(
                    $this->getModel(),
                    $this->getName(),
                    $config
                );
                $this->extraTypes = array_merge($this->extraTypes, $types);
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


}
