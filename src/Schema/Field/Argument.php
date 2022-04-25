<?php


namespace SilverStripe\GraphQL\Schema\Field;

use GraphQL\Error\SyntaxError;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SignatureProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\TypeReference;
use SilverStripe\View\ViewableData;

/**
 * An abstraction of a field argument
 */
class Argument implements ConfigurationApplier, SignatureProvider
{
    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|EncodedType|TypeReference
     */
    private $type;

    /**
     * @var string|int|bool|null
     */
    private $defaultValue;

    /**
     * @var string|null
     */
    private $description;

    /**
     * Argument constructor.
     * @param string $name
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config)
    {
        Schema::assertValidName($name);
        $this->name = $name;
        Schema::invariant(
            is_string($config) || is_array($config),
            '%::%s requires a string type name or an array as a second parameter',
            __CLASS__,
            __FUNCTION__
        );

        $appliedConfig = is_string($config) ? ['type' => $config] : $config;
        $this->applyConfig($appliedConfig);
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, ['description', 'defaultValue', 'type']);
        $type = $config['type'] ?? null;
        Schema::invariant(
            $type,
            'No type provided for argument %s',
            $this->getName()
        );
        $this->setType($type);

        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        if (isset($config['defaultValue'])) {
            $this->setDefaultValue($config['defaultValue']);
        }
    }

    /**
     * @param string|EncodedType $type
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setType($type): self
    {
        Schema::invariant(
            is_string($type) || $type instanceof EncodedType,
            'Type on arg %s must be a string or instance of %s',
            $this->getName(),
            EncodedType::class
        );
        if (is_string($type)) {
            $ref = TypeReference::create($type);
            $this->setDefaultValue($ref->getDefaultValue());
        }
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Argument
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return EncodedType
     */
    public function getEncodedType(): EncodedType
    {
        if ($this->type instanceof EncodedType) {
            return $this->type;
        }

        $ref = TypeReference::create($this->type);

        return EncodedType::create($ref);
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
     * @return Argument
     */
    public function setDefaultValue($defaultValue): self
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
     * @return Argument
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getSignature(): string
    {
        $components = [
            $this->getName(),
            $this->getEncodedType()->encode(),
            $this->getDescription(),
            $this->getDefaultValue(),
        ];

        return md5(json_encode($components) ?? '');
    }
}
