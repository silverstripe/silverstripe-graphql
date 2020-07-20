<?php


namespace SilverStripe\GraphQL\Schema;

use ReflectionException;

class ModelFieldAbstraction extends FieldAbstraction
{
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
     * ModelFieldAbstraction constructor.
     * @param string $name
     * @param $config
     * @param SchemaModelInterface $model
     * @throws SchemaBuilderException
     * @throws ReflectionException
     */
    public function __construct(string $name, $config, SchemaModelInterface $model)
    {
        $this->setModel($model);
        list($fieldName, $args) = static::parseName($name);
        $property = $config['property'] ?? $fieldName;

        SchemaBuilder::invariant(
            $this->getModel()->hasField($property),
            'DataObject %s does not have a field "%s"',
            $this->getModel()->getSourceClass(),
            $property
        );
        SchemaBuilder::invariant(
            is_array($config) || is_string($config) || $config === true,
            'Config for field %s must be a string representing a type, a map of config or a value of true
                to for type introspection from the model.',
            $fieldName
        );

        $fieldConfig = [
            'args' => $args,
            'type' => is_string($config) ? $config : $this->getModel()->getTypeForField($property),
        ];
        if (is_array($config)) {
            $fieldConfig = array_merge($fieldConfig, $config);
        }
        SchemaBuilder::invariant(
            $fieldConfig['type'],
            'Could not introspect type for field %s',
            $fieldName
        );

        if ($property !== $fieldName) {
            $this->addResolverContext('propertyMapping', [
                $fieldName => $property,
            ]);
        }
        $resolver = $fieldConfig['resolver'] ?? null;
        if (!$resolver) {
            $fieldConfig['resolver'] = $this->getModel()->getDefaultResolver($this->getResolverContext());
        }

        $this->modelTypeFields = $fieldConfig['fields'] ?? null;
        $this->modelTypeOperations = $fieldConfig['operations'] ?? null;

        unset($fieldConfig['fields']);
        unset($fieldConfig['operations']);
        unset($fieldConfig['property']);

        parent::__construct($fieldName, $fieldConfig);
    }

    /**
     * @return ModelAbstraction|null
     * @throws SchemaBuilderException
     */
    public function getModelType(): ?ModelAbstraction
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
     * @return ModelFieldAbstraction
     */
    public function setModel(SchemaModelInterface $model): ModelFieldAbstraction
    {
        $this->model = $model;
        return $this;
    }
}
