<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Interfaces\SignatureProvider;
use SilverStripe\GraphQL\Schema\Plugin\PluginConsumer;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use Exception;

/**
 * Abstraction for a generic type
 */
class Type implements ConfigurationApplier, SchemaValidator, SignatureProvider, SchemaComponent
{
    use Configurable;
    use Injectable;
    use PluginConsumer;

    private string $name;

    /**
     * @var Field[]
     */
    protected array $fields = [];

    private ?string $description = null;

    private array $interfaces = [];

    private bool $isInput = false;

    private ?ResolverReference $fieldResolver = null;

    /**
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, ?array $config = null)
    {
        $this->setName($name);
        if ($config) {
            $this->applyConfig($config);
        }
    }

    /**
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = ucfirst($name ?? '');
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

    public function getFieldByName(string $fieldName): ?Field
    {
        return $this->fields[$fieldName] ?? null;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
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
            $clonedField = clone $field;
            $existing = $this->fields[$field->getName()] ?? null;
            if (!$existing) {
                $this->fields[$field->getName()] = $clonedField;
            } else {
                $this->fields[$field->getName()] = $existing->mergeWith($clonedField);
            }
        }

        $this->mergePlugins($type->getPlugins());

        $this->setInterfaces(
            array_unique(
                array_merge(
                    $this->interfaces,
                    $type->getInterfaces()
                )
            )
        );

        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            !empty($this->getFields()),
            'Fields cannot be empty for type %s',
            $this->getName()
        );
        foreach ($this->getFields() as $field) {
            $field->validate();
        }
    }

    public function exists(): bool
    {
        return !empty($this->getFields());
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getEncodedInterfaces(): string
    {
        return var_export($this->interfaces, true);
    }

    public function setInterfaces(array $interfaces): self
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    public function addInterface(string $name): self
    {
        if (!in_array($name, $this->interfaces ?? [])) {
            $this->interfaces[] = $name;
        }

        return $this;
    }

    public function implements(string $interfaceName): bool
    {
        return in_array($interfaceName, $this->interfaces ?? []);
    }

    public function getIsInput(): bool
    {
        return $this->isInput;
    }

    public function setIsInput(bool $isInput): self
    {
        $this->isInput = $isInput;
        return $this;
    }

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

    /**
     * A deterministic representation of everything that gets encoded into the template.
     * Used as a cache key. This method will need to be updated if new data is added
     * to the generated code.
     * @throws Exception
     */
    public function getSignature(): string
    {
        $interfaces = $this->getInterfaces();
        sort($interfaces);
        $fields = $this->getFields();
        usort($fields, function (Field $a, Field $z) {
            return $a->getName() <=> $z->getName();
        });
        $components = [
            $this->getName(),
            (int) $this->getIsInput(),
            $this->getDescription(),
            $this->getSortedPlugins(),
            $interfaces,
            array_map(function (Field $field) {
                // The field resolver can change depending on what type it's on, so
                // we need to augment the Field signature here to be type specific.
                return $field->getSignature() . $field->getEncodedResolver($this->getName())->getExpression();
            }, $fields ?? []),
        ];

        return md5(json_encode($components) ?? '');
    }
}
