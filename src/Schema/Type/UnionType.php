<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Interfaces\SignatureProvider;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Abstraction of a union type
 */
class UnionType implements
    SchemaValidator,
    ConfigurationApplier,
    SignatureProvider,
    SchemaComponent
{

    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var ResolverReference
     */
    private $typeResolver;

    /**
     * @var string|null
     */
    private $description;

    /**
     * Union constructor.
     * @param string $name
     * @param array|null $config
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
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, ['typeResolver', 'types', 'description']);
        if (isset($config['typeResolver'])) {
            $this->setTypeResolver($config['typeResolver']);
        }
        if (isset($config['types'])) {
            $this->setTypes($config['types']);
        }
        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
    }

    /**
     * @return mixed|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return UnionType
     * @throws SchemaBuilderException
     */
    public function setName(string $name)
    {
        Schema::assertValidName($name);
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array $types
     * @return UnionType
     */
    public function setTypes(array $types): UnionType
    {
        $this->types = $types;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncodedTypes(): string
    {
        return var_export($this->types, true);
    }

    /**
     * @return mixed
     */
    public function getTypeResolver()
    {
        return $this->typeResolver;
    }

    /**
     * @param array|string|ResolverReference|null $resolver
     * @return $this
     */
    public function setTypeResolver($resolver): self
    {
        if ($resolver) {
            $this->typeResolver = $resolver instanceof ResolverReference
                ? $resolver
                : ResolverReference::create($resolver);
        } else {
            $this->typeResolver = null;
        }

        return $this;
    }

    /**
     * @return EncodedResolver
     */
    public function getEncodedTypeResolver(): EncodedResolver
    {
        return EncodedResolver::create($this->typeResolver);
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
     * @return UnionType
     */
    public function setDescription(?string $description): UnionType
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param UnionType $existing
     * @throws SchemaBuilderException
     */
    public function mergeWith(UnionType $existing)
    {
        $this->setName($existing->getName());
        $this->setTypes(array_unique(
            array_merge(
                $this->getTypes(),
                $existing->getTypes()
            )
        ));
        if ($existing->getTypeResolver()) {
            $this->setTypeResolver($existing->getTypeResolver());
        }

        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            $this->typeResolver,
            'Union %s has no type resolver',
            $this->getName()
        );

        Schema::invariant(
            count($this->types),
            'Union %s has no types',
            $this->getName()
        );
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        $types = $this->getTypes();
        sort($types);
        $components = [
            $this->getName(),
            $types,
            $this->typeResolver->toString(),
            $this->getDescription(),
        ];

        return md5(json_encode($components));
    }
}
