<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class TypeAbstraction extends ViewableData implements ConfigurationApplier
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var FieldAbstraction[]
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
     * TypeAbstraction constructor.
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
        SchemaBuilder::assertValidConfig($config, [
            'fields',
            'description',
            'interfaces',
            'isInput',
        ]);

        $fields = $config['fields'] ?? [];
        SchemaBuilder::invariant(count($fields), 'Fields cannot be empty for type %s', $this->getName());

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
     * @return TypeAbstraction
     */
    public function setName(string $name): TypeAbstraction
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
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
     * @return TypeAbstraction
     * @throws SchemaBuilderException
     */
    public function applyFieldsConfig(array $fields): TypeAbstraction
    {
        SchemaBuilder::assertValidConfig($fields);
        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig === false) {
                continue;
            }
            $abstract = $fieldConfig instanceof FieldAbstraction
                ? $fieldConfig
                : FieldAbstraction::create(
                    $fieldName,
                    $fieldConfig
                );

            $this->fields[$fieldName] = $abstract;
        }

        return $this;
    }

    /**
     * @param FieldAbstraction[] $fields
     * @return TypeAbstraction
     * @throws SchemaBuilderException
     */
    public function setFields(array $fields): TypeAbstraction
    {
        /* @var FieldAbstraction $fieldAbstract */
        foreach ($fields as $fieldAbstract) {
            SchemaBuilder::invariant(
                $fieldAbstract instanceof FieldAbstraction,
                '%s takes an array of %s instances',
                __FUNCTION__,
                FieldAbstraction::class
            );
            $this->fields[$fieldAbstract->getName()] = $fieldAbstract;
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
     * @param mixed $description
     * @return TypeAbstraction
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
     * @return TypeAbstraction
     */
    public function setInterfaces(array $interfaces): TypeAbstraction
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
     * @return TypeAbstraction
     */
    public function setIsInput(bool $isInput): TypeAbstraction
    {
        $this->isInput = $isInput;
        return $this;
    }

}
