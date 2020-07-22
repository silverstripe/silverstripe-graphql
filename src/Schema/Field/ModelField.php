<?php


namespace SilverStripe\GraphQL\Schema\Field;

use ReflectionException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class ModelField extends Field
{
    const INTROSPECT_TYPE = '__INTROSPECT_TYPE__';

    /**
     * @var SchemaModelInterface
     */
    private $model;

    /**
     * @var array|null
     */
    private $modelTypeFields = null;

    /**
     * @var array|null
     */
    private $modelTypeOperations = null;

    /**
     * @var string
     */
    private $property;

    /**
     * ModelField constructor.
     * @param string $name
     * @param $config
     * @param SchemaModelInterface $model
     * @throws SchemaBuilderException
     * @throws ReflectionException
     */
    public function __construct(string $name, $config, SchemaModelInterface $model)
    {
        $this->setModel($model);
        Schema::invariant(
            is_array($config) || is_string($config) || $config === true,
            'Config for field %s must be a string representing a type, a map of config or a value of true
                to for type introspection from the model.',
            $name
        );
        $this->setResolver($model->getDefaultResolver());

        if ($config === true) {
            $config = [
                'type' => true,
            ];
        }

        $this->setProperty($config['property'] ?? null);
        parent::__construct($name, $config);
    }

    public function applyConfig(array $config)
    {
        Schema::invariant(
            $this->getModel()->hasField($this->getFieldName()),
            'DataObject %s does not have a field "%s"',
            $this->getModel()->getSourceClass(),
            $this->getFieldName()
        );
        $type = $config['type'] ?? true;
        if ($type === true) {
            $config['type'] = $this->getModel()->getTypeForField($this->getFieldName());
        }
        $resolver = $config['resolver'] ?? null;
        if (!$resolver) {
            $config['resolver'] = $this->getModel()->getDefaultResolver($this->getResolverContext());
        }

        $this->modelTypeFields = $config['fields'] ?? null;
        $this->modelTypeOperations = $config['operations'] ?? null;

        unset($config['fields']);
        unset($config['operations']);
        unset($config['property']);

        parent::applyConfig($config);
    }

    /**
     * @param EncodedType|string $type
     * @return Field
     * @throws SchemaBuilderException
     */
    public function applyType($type): Field
    {
        $fieldType = $type === self::INTROSPECT_TYPE
            ? $this->getModel()->getTypeForField($this->getFieldName())
            : $type;

        return parent::applyType($fieldType);
    }

    /**
     * @return array|null
     */
    public function getResolverContext(): ?array
    {
        $context = [];
        if ($this->getProperty() && $this->getProperty() !== $this->getName()) {
            $context = [
                'propertyMapping' => [
                    $this->getName() => $this->getProperty(),
                ]
            ];
        }

        return array_merge(parent::getResolverContext(), $context);
    }

    /**
     * @return ModelType|null
     * @throws SchemaBuilderException
     */
    public function getModelType(): ?ModelType
    {
        $model = $this->getModel()->getModelField($this->getName());
        if ($model) {
            $config = [];
            if ($this->modelTypeFields) {
                $config['fields'] = $this->modelTypeFields;
            }
            if ($this->modelTypeOperations) {
                $config['operations'] = $this->modelTypeOperations;
            }
            $model->applyConfig($config);
        }

        return $model;
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
     * @return ModelField
     */
    public function setModel(SchemaModelInterface $model): ModelField
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @param string|null $property
     * @return ModelField
     */
    public function setProperty(?string $property): ModelField
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->getProperty() ?: $this->getName();
    }


}
