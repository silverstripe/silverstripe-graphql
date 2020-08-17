<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Defines a GraphQL interface. It may seem counter-intuitive that an abstraction would inherit
 * from the concretion, but since these are just value objects that end up getting rendered
 * as code, an unconventional architecture is probably okay. The irrelevant fields just
 * just ignored in rendering.
 */
class InterfaceType extends Type
{
    /**
     * @var ResolverReference
     */
    private $typeResolver;

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, [
            'fields',
            'description',
            'typeResolver',
        ]);
        if (isset($config['typeResolver'])) {
            $this->setTypeResolver($config['typeResolver']);
        }
        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        $fields = $config['fields'] ?? [];
        $this->setFields($fields);
    }

    /**
     * @return EncodedResolver
     */
    public function getEncodedTypeResolver(): EncodedResolver
    {
        return EncodedResolver::create($this->typeResolver);
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

    public function validate(): void
    {
        Schema::invariant(
            !empty($this->getFields()),
            'Interface %s has no fields',
            $this->getName()
        );

        Schema::invariant(
            $this->typeResolver,
            'Interface %s has no type resolver',
            $this->getName()
        );
    }

    /**
     * @return DBHTMLText
     */
    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith('SilverStripe\\GraphQL\\Schema\\Interface');
    }

}
