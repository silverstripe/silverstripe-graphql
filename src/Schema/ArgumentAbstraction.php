<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\View\ViewableData;
use InvalidArgumentException;

class ArgumentAbstraction extends ViewableData implements ConfigurationApplier
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var EncodedType
     */
    private $encodedType;

    /**
     * @var string|int|bool|null
     */
    private $defaultValue;

    /**
     * @var string|null
     */
    private $description;

    /**
     * ArgumentAbstraction constructor.
     * @param string $name
     * @param $type
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $type, array $config = [])
    {
        parent::__construct();
        SchemaBuilder::assertValidName($name);
        $this->name = $name;
        $this->setType($type);
    }

    /**
     * @param string|TypeReference|EncodedType $type
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setType($type): self
    {
        if ($type instanceof EncodedType) {
            $this->encodedType = $type;

            return $this;
        }

        if (!is_string($type) && !$type instanceof TypeReference) {
            throw new InvalidArgumentException(sprintf(
                'Illegal type passed to argument "%"',
                $this->name
            ));
        }

        $ref = $type instanceof TypeReference ? $type : TypeReference::create($type);

        try {
            $this->encodedType = EncodedType::create($ref->toAST());
        } catch (SyntaxError $e) {
            throw new SchemaBuilderException(sprintf(
                'The type for argument "%s" is not properly formatted',
                $this->name
            ));
        }

        return $this;
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        SchemaBuilder::assertValidConfig($config, ['description', 'defaultValue', 'type']);
        $description = $config['description'] ?? null;
        $default = $config['defaultValue'] ?? null;

        $this->setDescription($description);
        $this->setDefaultValue($default);
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
     * @return ArgumentAbstraction
     */
    public function setName(string $name): ArgumentAbstraction
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return EncodedType
     */
    public function getEncodedType(): EncodedType
    {
        return $this->encodedType;
    }

    /**
     * @param EncodedType $encodedType
     * @return ArgumentAbstraction
     */
    public function setEncodedType(EncodedType $encodedType): ArgumentAbstraction
    {
        $this->encodedType = $encodedType;
        return $this;
    }

    /**
     * @return bool|int|string|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param bool|int|string|null $defaultValue
     * @return ArgumentAbstraction
     */
    public function setDefaultValue($defaultValue): ArgumentAbstraction
    {
        $this->defaultValue = $defaultValue;
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
     * @return ArgumentAbstraction
     */
    public function setDescription(?string $description): ArgumentAbstraction
    {
        $this->description = $description;
        return $this;
    }


}
