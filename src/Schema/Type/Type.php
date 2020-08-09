<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Plugin\PluginConsumer;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

/**
 * Abstraction for a generic type
 */
class Type extends ViewableData implements ConfigurationApplier, SchemaValidator
{
    use PluginConsumer;

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
     * @var ResolverReference|null
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
            'plugins',
        ]);
        if (isset($config['fieldResolver'])) {
            $this->setFieldResolver($config['fieldResolver']);
        }
        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        if (isset($config['interfaces'])) {
            $this->setInterfaces($config['interfaces']);
        }
        if (isset($config['isInput'])) {
            $this->setIsInput($config['isInput']);
        }
        if (isset($config['plugins'])) {
            $this->setPlugins($config['plugins']);
        }

        $fields = $config['fields'] ?? [];
        $this->setFields($fields);
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
    public function setName(string $name): self
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
    public function setFields(array $fields): self
    {
        Schema::assertValidConfig($fields);
        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig === false) {
                continue;
            }
            $this->addField($fieldName, $fieldConfig);
        }

        return $this;
    }

    /**
     * @param string $fieldName
     * @param string|array|Field $fieldConfig
     * @param callable|null $callback
     * @return Type
     */
    public function addField(string $fieldName, $fieldConfig, ?callable $callback = null): self
    {
        if (!$fieldConfig instanceof Field) {
            $config = is_string($fieldConfig) ? ['type' => $fieldConfig] : $fieldConfig;
            $fieldObj = Field::create($fieldName, $config);
        } else {
            $fieldObj = $fieldConfig;
        }

        $defaultResolver = $fieldObj->getDefaultResolver();
        if (!$defaultResolver) {
            $fieldObj->setDefaultResolver($this->getFieldResolver());
        }
        $this->fields[$fieldObj->getName()] = $fieldObj;
        if ($callback) {
            call_user_func_array($callback, [$fieldObj]);
        }
        return $this;
    }

    /**
     * @param string $field
     * @return Type
     */
    public function removeField(string $field): self
    {
        unset($this->fields[$field]);

        return $this;
    }

    /**
     * @param string $fieldName
     * @return Field|null
     */
    public function getFieldByName(string $fieldName): ?Field
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param Type $type
     * @return Type
     * @throws SchemaBuilderException
     */
    public function mergeWith(Type $type): self
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

        $this->mergePlugins($type->getPlugins());

        $this->setInterfaces(array_merge($this->interfaces, $type->getInterfaces()));

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
            $field->validate();
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
     * @return string
     */
    public function getEncodedInterfaces(): string
    {
        return var_export($this->interfaces, true);
    }

    /**
     * @param array $interfaces
     * @return Type
     */
    public function setInterfaces(array $interfaces): self
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function addInterface(string $name): self
    {
        if (!in_array($name, $this->interfaces)) {
            $this->interfaces[] = $name;
        }

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
    public function setIsInput(bool $isInput): self
    {
        $this->isInput = $isInput;
        return $this;
    }

    /**
     * @return ResolverReference|null
     */
    public function getFieldResolver(): ?ResolverReference
    {
        return $this->fieldResolver;
    }

    /**
     * @param array|string|ResolverReference|null $fieldResolver
     * @return Type
     */
    public function setFieldResolver($fieldResolver): self
    {
        if ($fieldResolver) {
            $this->fieldResolver = $fieldResolver instanceof ResolverReference
                ? $fieldResolver
                : ResolverReference::create($fieldResolver);
        } else {
            $this->fieldResolver = null;
        }
        return $this;
    }
}
