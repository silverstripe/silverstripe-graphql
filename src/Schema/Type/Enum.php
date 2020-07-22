<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class Enum extends ViewableData implements SchemaValidator
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
     * Enum constructor.
     * @param string $name
     * @param array $values
     * @param string|null $description
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, array $values, ?string $description = null)
    {
        parent::__construct();
        Schema::assertValidName($name);
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
        Schema::invariant(
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
     * @return Enum
     */
    public function setName(string $name): Enum
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
     * @return Enum
     */
    public function setValues(array $values): Enum
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
     * @return Enum
     */
    public function setDescription(?string $description): Enum
    {
        $this->description = $description;
        return $this;
    }


}
