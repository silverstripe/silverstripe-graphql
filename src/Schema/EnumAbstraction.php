<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class EnumAbstraction extends ViewableData implements SchemaValidator
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string|null
     */
    private $description;

    /**
     * EnumAbstraction constructor.
     * @param string $name
     * @param array $values
     * @param string|null $description
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, array $values, ?string $description = null)
    {
        parent::__construct();
        SchemaBuilder::assertValidName($name);
        $this->setName($name);
        $this->setValues($values);
        $this->setDescription($description);
    }

    /**
     * @return ArrayList
     */
    public function getValueList(): ArrayList
    {
        $list = ArrayList::create();
        foreach ($this->getValues() as $key => $val) {
            $list->push(ArrayData::create([
                'Key' => is_string($key) ? $key : null,
                'Value' => $val,
            ]));
        }

        return $list;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        SchemaBuilder::invariant(
            $this->getValueList()->exists(),
            'Enum type %s has no values defined',
            $this->getName()
        );
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
     * @return EnumAbstraction
     */
    public function setName(string $name): EnumAbstraction
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     * @return EnumAbstraction
     */
    public function setValues(array $values): EnumAbstraction
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return EnumAbstraction
     */
    public function setDescription(?string $description): EnumAbstraction
    {
        $this->description = $description;
        return $this;
    }


}
