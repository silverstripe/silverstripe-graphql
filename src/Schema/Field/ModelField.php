<?php


namespace SilverStripe\GraphQL\Schema\Field;

use SilverStripe\GraphQL\Config\Configuration;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelFieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * A field that appears on model type
 */
class ModelField extends Field
{
    use ModelAware;

    /**
     * @var array|null
     */
    private $modelTypeFields = null;

    /**
     * @var string|null
     */
    private $resolvedModelClass = null;

    /**
     * @var string
     */
    private $property;

    /**
     * @var Configuration
     */
    private $metadata;

    /**
     * ModelField constructor.
     * @param string $name
     * @param $config
     * @param SchemaModelInterface $model The model containing this field (different from the model this field might resolve to)
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config, SchemaModelInterface $model)
    {
        $this->metadata = new Configuration();
        $this->setModel($model);
        Schema::invariant(
            is_array($config) || is_string($config) || $config === true,
            'Config for field %s must be a string representing a type, a map of config or a value of true
                to for type introspection from the model.',
            $name
        );
        if (is_string($config)) {
            $config = ['type' => $config];
        }
        if (!is_array($config)) {
            $config = [];
        }

        parent::__construct($name, $config);
    }

    public function applyConfig(array $config)
    {
        $type = $config['type'] ?? null;
        if ($type) {
            $this->setType($type);
        }

        if (isset($config['property'])) {
            $this->setProperty($config['property']);
        }

        $resolver = $config['resolver'] ?? null;
        if ($resolver) {
            $this->setResolver($resolver);
        } else {
            $this->setResolver($this->getModel()->getDefaultResolver($this->getResolverContext()));
        }

        if (isset($config['resolvedModelClass'])) {
            $this->resolvedModelClass = $config['resolvedModelClass'];
        }

        $this->modelTypeFields = $config['fields'] ?? null;

        unset($config['fields']);
        unset($config['operations']);
        unset($config['property']);

        parent::applyConfig($config);
    }

    /**
     * For nested field definitions
     * Blog:
     *   fields:
     *     Comments:
     *       fields:
     *         Author:
     *           fields:
     *             Name: String
     * @return ModelType|null
     * @throws SchemaBuilderException
     */
    public function getModelType(): ?ModelType
    {
        $type = $this->getNamedType();
        if (Schema::isInternalType($type)) {
            return null;
        }

        $model = $this->getModel()->getModelTypeForField($this->getName(), $this->resolvedModelClass);
        if ($model) {
            $config = [];
            if ($this->modelTypeFields) {
                $config['fields'] = $this->modelTypeFields;
            }
            $model->applyConfig($config);
        }

        return $model;
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
    public function setProperty(?string $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->getProperty() ?: $this->getModel()->getPropertyForField($this->getName());
    }

    /**
     * @return Configuration
     */
    public function getMetadata(): Configuration
    {
        return $this->metadata;
    }

    public function mergeWith(Field $field): Field
    {
        if ($field->getProperty()) {
            $this->setProperty($field->getProperty());
        }
        return parent::mergeWith($field);
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getSignature(): string
    {
        $parentSignature = parent::getSignature();
        if (!$this->getProperty()) {
            return $parentSignature;
        }
        return md5($parentSignature . $this->getProperty());
    }

    /**
     * @param string $pluginName
     * @param $plugin
     * @throws SchemaBuilderException
     */
    public function validatePlugin(string $pluginName, $plugin): void
    {
        Schema::invariant(
            $plugin && ($plugin instanceof ModelFieldPlugin || $plugin instanceof FieldPlugin),
            'Plugin %s not found or does not apply to field "%s"',
            $pluginName,
            $this->getName()
        );
    }
}
