<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ExtraTypeProvider;
use SilverStripe\GraphQL\Schema\Interfaces\InputTypeProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ModelBlacklist;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\OperationProvider;
use SilverStripe\GraphQL\Schema\Interfaces\RequiredFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Registry\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\Schema;

class ModelType extends Type implements ExtraTypeProvider
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

        parent::__construct($type);

        $this->applyConfig($config);
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, ['fields', 'operations']);

        $fieldConfig = $config['fields'] ?? [];
        if ($fieldConfig === Schema::ALL) {
            $this->addAllFields();
        } else {
            $fields = array_merge($this->getBaseFields(), $fieldConfig);
            $this->applyFieldsConfig($fields);
        }

        $operations = $config['operations'] ?? null;
        if (!$operations) {
            return;
        }
        if ($operations === Schema::ALL) {
            $this->addAllOperations();
        } else {
            $this->applyOperationsConfig($operations);
        }
    }

    /**
     * @param array $fields
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function applyFieldsConfig(array $fields): Type
    {
        Schema::assertValidConfig($fields);

        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            $this->addField($fieldName, $data);
        }

        return $this;
    }

    /**
     * @param Field|string $field
     * @param array|string|null $config
     * @return Type
     * @throws SchemaBuilderException
     */
    public function addField($field, $config = null): Type
    {
        Schema::invariant(
            is_string($field) || $field instanceof ModelField,
            'Argument 1 for %s::%s must be an field name or an instance of %s',
            __CLASS__,
            __FUNCTION__,
            ModelField::class
        );

        $fieldObj = $field instanceof ModelField
            ? $field
            : ModelField::create($field, $config, $this->getModel());

        Schema::invariant(
            !in_array(strtolower($fieldObj->getName()), $this->blacklistedFields),
            'Field %s is not allowed on %s',
            $fieldObj->getName(),
            $this->getModel()->getSourceClass()
        );

        $this->fields[$fieldObj->getName()] = $fieldObj;

        return $this;
    }

    /**
     * @return ModelType
     * @throws SchemaBuilderException
     */
    public function addAllFields(): ModelType
    {
        /* @var SchemaModelInterface&DefaultFieldsProvider&RequiredFieldsProvider $model */
        $defaultFields = $model instanceof DefaultFieldsProvider ? $model->getDefaultFields() : [];
        $requiredFields = $model instanceof RequiredFieldsProvider ? $model->getRequiredFields() : [];
        foreach (array_merge($defaultFields, $requiredFields) as $fieldName => $fieldType) {
            $this->addField($fieldName, $fieldType);
        }
        $allFields = $this->getModel()->getAllFields();
        foreach ($allFields as $fieldName) {
            $this->addField($fieldName, $this->getModel()->getTypeForField($fieldName));
        }
    }

    /**
     * @return Type
     * @throws SchemaBuilderException
     */
    public function addAllOperations(): Type
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
     * @param string $operationName
     * @param array $config
     * @return Type
     */
    public function addOperation(string $operationName, array $config = []): Type
    {
        $this->operationCreators[$operationName] = $config;

        return $this;
    }

    /**
     * @return Field[]
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


        return $operations;
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
     * @return SchemaModelInterface
     */
    public function getModel(): SchemaModelInterface
    {
        return $this->model;
    }

    /**
     * @param SchemaModelInterface $model
     * @return ModelType
     */
    public function setModel(SchemaModelInterface $model): ModelType
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
