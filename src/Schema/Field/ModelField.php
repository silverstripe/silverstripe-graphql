<?php


namespace SilverStripe\GraphQL\Schema\Field;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
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
     * @var string
     */
    private $property;

    /**
     * ModelField constructor.
     * @param string $name
     * @param $config
     * @param SchemaModelInterface $model
     * @throws SchemaBuilderException
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
        }
        $defaultResolver = $config['defaultResolver'] ?? null;
        if (!$defaultResolver) {
            $config['defaultResolver'] = $this->getModel()->getDefaultResolver($this->getResolverContext());
        }

        $this->modelTypeFields = $config['fields'] ?? null;

        unset($config['fields']);
        unset($config['operations']);
        unset($config['property']);

        parent::applyConfig($config);
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
        $model = $this->getModel()->getModelTypeForField($this->getName());
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
        return $this->getProperty() ?: $this->getName();
    }


}
