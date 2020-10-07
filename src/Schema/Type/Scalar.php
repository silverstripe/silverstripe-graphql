<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Interfaces\SignatureProvider;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;

class Scalar implements ConfigurationApplier, SchemaValidator, SignatureProvider, SchemaComponent
{
    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ResolverReference
     */
    private $serialiser;

    /**
     * @var ResolverReference
     */
    private $valueParser;

    /**
     * @var ResolverReference
     */
    private $literalParser;

    /**
     * Scalar constructor.
     * @param string $fieldName
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $fieldName, array $config = [])
    {
        $this->setName($fieldName);
        $this->applyConfig($config);
    }

    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, [
            'name',
            'serialiser',
            'valueParser',
            'literalParser',
        ]);

        if (isset($config['name'])) {
            $this->setName($config['name']);
        }

        if (isset($config['serialiser'])) {
            $this->setSerialiser(ResolverReference::create($config['serialiser']));
        }

        if (isset($config['valueParser'])) {
            $this->setValueParser(ResolverReference::create($config['valueParser']));
        }

        if (isset($config['literalParser'])) {
            $this->setLiteralParser(ResolverReference::create($config['literalParser']));
        }
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
     * @return Scalar
     * @throws SchemaBuilderException
     */
    public function setName(string $name): self
    {
        Schema::assertValidName($name);
        $this->name = $name;
        return $this;
    }

    /**
     * @return ResolverReference
     */
    public function getSerialiser(): ResolverReference
    {
        return $this->serialiser;
    }

    /**
     * @return EncodedResolver
     */
    public function getEncodedSerialiser(): EncodedResolver
    {
        return EncodedResolver::create($this->getSerialiser());
    }

    /**
     * @param ResolverReference $serialiser
     * @return Scalar
     */
    public function setSerialiser(ResolverReference $serialiser): Scalar
    {
        $this->serialiser = $serialiser;
        return $this;
    }

    /**
     * @return ResolverReference
     */
    public function getValueParser(): ResolverReference
    {
        return $this->valueParser;
    }

    /**
     * @return EncodedResolver
     */
    public function getEncodedValueParser(): EncodedResolver
    {
        return EncodedResolver::create($this->getValueParser());
    }

    /**
     * @param ResolverReference $valueParser
     * @return Scalar
     */
    public function setValueParser(ResolverReference $valueParser): Scalar
    {
        $this->valueParser = $valueParser;
        return $this;
    }

    /**
     * @return ResolverReference
     */
    public function getLiteralParser(): ResolverReference
    {
        return $this->literalParser;
    }

    /**
     * @return EncodedResolver
     */
    public function getEncodedLiteralParser(): EncodedResolver
    {
        return EncodedResolver::create($this->getLiteralParser());
    }

    /**
     * @param ResolverReference $literalParser
     * @return Scalar
     */
    public function setLiteralParser(ResolverReference $literalParser): Scalar
    {
        $this->literalParser = $literalParser;
        return $this;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            $this->getSerialiser() && $this->getLiteralParser() && $this->getValueParser(),
            'Scalar type %s must have serialiser, literalParser, and valueParser functions defined'
        );
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return md5(json_encode([
            $this->getName(),
            $this->getSerialiser()->toArray(),
            $this->getLiteralParser()->toArray(),
            $this->getValueParser()->toArray(),
        ]));
    }
}
