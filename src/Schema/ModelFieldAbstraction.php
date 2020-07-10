<?php


namespace SilverStripe\GraphQL\Schema;


class ModelFieldAbstraction extends FieldAbstraction
{
    private $model;

    public function __construct(string $name, $config, SchemaModelInterface $model)
    {
        $this->setModel($model);
        list($fieldName, $args) = static::parseName($name);
        SchemaBuilder::invariant(
            $this->getModel()->hasField($fieldName),
            'DataObject %s does not have a field "%s"',
            $this->getModel()->getSourceClass(),
            $fieldName
        );
        SchemaBuilder::invariant(
            is_array($config) || is_string($config) || $config === true,
            'Config for field %s must be a string representing a type, a map of config or a value of true
                to for type introspection from the model.',
            $fieldName
        );

        $fieldConfig = [
            'args' => $args,
        ];
        if ($config === true) {
            $type = $this->getModel()->getTypeForField($fieldName);
            SchemaBuilder::invariant(
                $type,
                'Could not introspect type for field %s',
                $fieldName
            );
            $fieldConfig['type'] = $type;
        } else if (is_string($config)) {
            $fieldConfig['type'] = $config;
        } else {
            $fieldConfig = array_merge($fieldConfig, $config);
        }
        $resolver = $fieldConfig['resolver'] ?? null;
        if (!$resolver) {
            $fieldConfig['resolver'] = $this->getModel()->getDefaultResolver();
        }

        parent::__construct($fieldName, $fieldConfig);
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
