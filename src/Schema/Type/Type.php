<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class Type extends ViewableData implements ConfigurationApplier, SchemaValidator
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Field[]
     */
    protected $fields = [];

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var array
     */
    private $interfaces = [];

    /**
     * @var bool
     */
    private $isInput = false;

    /**
     * @var array
     */
    private $fieldResolver;

    /**
     * Type constructor.
     * @param string $name
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, ?array $config = null)
    {
        parent::__construct();
        $this->setName($name);
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
        Schema::assertValidConfig($config, [
            'fields',
            'description',
            'interfaces',
            'isInput',
            'fieldResolver',
        ]);

        $fields = $config['fields'] ?? [];
        $this->setFieldResolver($config['fieldResolver'] ?? null);
        $this->applyFieldsConfig($fields);
        $this->setDescription($config['description'] ?? null);
        $this->setInterfaces($config['interfaces'] ?? []);
        $this->setIsInput($config['isInput'] ?? false);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Type
     */
    public function setName(string $name): Type
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return ArrayList
     */
    public function getFieldList(): ArrayList
    {
        return ArrayList::create(array_values($this->getFields()));
    }

    /**
     * @param array $fields
     * @return Type
     * @throws SchemaBuilderException
     */
    public function applyFieldsConfig(array $fields): Type
    {
        Schema::assertValidConfig($fields);
        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig === false) {
                continue;
            }
            $field = $fieldConfig instanceof Field
                ? $fieldConfig
                : Field::create(
                    $fieldName,
                    $fieldConfig
                );

            $defaultResolver = $field->getDefaultResolver();
            if (!$defaultResolver) {
                $field->setDefaultResolver($this->getFieldResolver());
            }
            $this->fields[$fieldName] = $field;
        }

        return $this;
    }

    /**
     * @param Field[] $fields
     * @return Type
     * @throws SchemaBuilderException
     */
    public function setFields(array $fields): Type
    {
        /* @var Field $field */
        foreach ($fields as $field) {
            Schema::invariant(
                $field instanceof Field,
                '%s takes an array of %s instances',
                __FUNCTION__,
                Field::class
            );
            $this->fields[$field->getName()] = $field;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Type $type
     * @return Type
     * @throws SchemaBuilderException
     */
    public function mergeWith(Type $type): Type
    {
        Schema::invariant(
            $type->getIsInput() === $this->getIsInput(),
            'Cannot merge an input type %s with an object type %s',
            $type->getName(),
            $this->getName()
        );
        foreach ($type->getFields() as $field) {
            $existing = $this->fields[$field->getName()] ?? null;
            if (!$existing) {
                $this->fields[$field->getName()] = $field;
            } else {
                $this->fields[$field->getName()] = $existing->mergeWith($field);
            }
        }

        foreach ($type->getInterfaces() as $interface) {
            // to do
        }

        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            $this->getFieldList()->exists(),
            'Fields cannot be empty for type %s', $this->getName()
        );
        foreach ($this->getFields() as $field) {
            $field->validate();;
        }
    }

    /**
     * @param mixed $description
     * @return Type
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @param array $interfaces
     * @return Type
     */
    public function setInterfaces(array $interfaces): Type
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsInput(): bool
    {
        return $this->isInput;
    }

    /**
     * @param bool $isInput
     * @return Type
     */
    public function setIsInput(bool $isInput): Type
    {
        $this->isInput = $isInput;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getFieldResolver(): ?array
    {
        return $this->fieldResolver;
    }

    /**
     * @param array|null $fieldResolver
     * @return Type
     */
    public function setFieldResolver(?array $fieldResolver): Type
    {
        $this->fieldResolver = $fieldResolver;
        return $this;
    }
}
