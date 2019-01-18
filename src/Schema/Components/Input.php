<?php

namespace SilverStripe\GraphQL\Schema\Components;

class Input extends AbstractType
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Field[]
     */
    protected $fields = [];

    /**
     * FieldCollection constructor.
     * @param $name
     * @param string $description
     * @param Field[] $fields
     */
    public function __construct($name, $description = null, $fields = [])
    {
        $this->setName($name)
            ->setDescription($description)
            ->setFields($fields);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function addField(Field $field)
    {
        $this->fields[$field->getName()] = $field;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'fields' => $this->getFields(),
            'description' => $this->getDescription()
        ];
    }
}