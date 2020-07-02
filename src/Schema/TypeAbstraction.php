<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class TypeAbstraction extends ViewableData
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var FieldAbstraction[]
     */
    private $fields = [];

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var array
     */
    private $interfaces = [];

    /**
     * TypeAbstraction constructor.
     * @param string $name
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, array $config)
    {
        parent::__construct();
        $this->name = $name;
        SchemaBuilder::assertValidConfig($config, ['fields', 'description', 'interfaces']);

        $fields = $config['fields'] ?? [];
        SchemaBuilder::invariant(count($fields), 'Fields cannot be empty for type %s', $name);

        $this->setFields($fields);
        $this->setDescription($config['description'] ?? null);
        $this->setInterfaces($config['interfaces'] ?? []);
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
        return ArrayList::create(array_values($this->fields));
    }

    /**
     * @param array $fields
     * @return TypeAbstraction
     * @throws SchemaBuilderException
     */
    public function setFields(array $fields): TypeAbstraction
    {
        SchemaBuilder::assertValidConfig($fields);
        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig === false) {
                continue;
            }
            $abstract = FieldAbstraction::create(
                $fieldName,
                $fieldConfig
            );
            $this->fields[$fieldName] = $abstract;
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

}
