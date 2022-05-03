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

    private string $name;

    private ResolverReference $serialiser;

    private ResolverReference $valueParser;

    private ResolverReference $literalParser;

    /**
     * @throws SchemaBuilderException
     */
    public function __construct(string $fieldName, array $config = [])
    {
        $this->setName($fieldName);
        $this->applyConfig($config);
    }

    public function applyConfig(array $config): void
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

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function setName(string $name): self
    {
        Schema::assertValidName($name);
        $this->name = $name;
        return $this;
    }

    public function getSerialiser(): ResolverReference
    {
        return $this->serialiser;
    }

    public function getEncodedSerialiser(): EncodedResolver
    {
        return EncodedResolver::create($this->getSerialiser());
    }

    public function setSerialiser(ResolverReference $serialiser): self
    {
        $this->serialiser = $serialiser;
        return $this;
    }

    public function getValueParser(): ResolverReference
    {
        return $this->valueParser;
    }

    public function getEncodedValueParser(): EncodedResolver
    {
        return EncodedResolver::create($this->getValueParser());
    }

    public function setValueParser(ResolverReference $valueParser): self
    {
        $this->valueParser = $valueParser;
        return $this;
    }

    public function getLiteralParser(): ResolverReference
    {
        return $this->literalParser;
    }

    public function getEncodedLiteralParser(): EncodedResolver
    {
        return EncodedResolver::create($this->getLiteralParser());
    }

    public function setLiteralParser(ResolverReference $literalParser): self
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

    public function getSignature(): string
    {
        return md5(json_encode([
            $this->getName(),
            $this->getSerialiser()->toArray(),
            $this->getLiteralParser()->toArray(),
            $this->getValueParser()->toArray(),
        ]) ?? '');
    }
}
