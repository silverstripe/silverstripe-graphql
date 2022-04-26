<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Exception;

/**
 * Defines a GraphQL interface. It may seem counter-intuitive that an abstraction would inherit
 * from the concretion, but since these are just value objects that end up getting rendered
 * as code, an unconventional architecture is probably okay. The irrelevant fields just
 * just ignored in rendering.
 */
class InterfaceType extends Type
{
    private ResolverReference $typeResolver;

    /**
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

    /**
     * @throws SchemaBuilderException
     */
    public function mergeWith(Type $type): Type
    {
        Schema::invariant(
            $type instanceof InterfaceType,
            '%s::%s only accepts instances of %s',
            __CLASS__,
            __FUNCTION__,
            InterfaceType::class
        );
        foreach ($type->getFields() as $field) {
            $clonedField = clone $field;
            $existing = $this->fields[$field->getName()] ?? null;
            if (!$existing) {
                $this->fields[$field->getName()] = $clonedField;
            } else {
                $this->fields[$field->getName()] = $existing->mergeWith($clonedField);
            }
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
     * @throws Exception
     */
    public function getSignature(): string
    {
        $fields = $this->getFields();
        usort($fields, function (Field $a, Field $z) {
            return $a->getName() <=> $z->getName();
        });

        $components = [
            $this->getName(),
            $this->typeResolver->toString(),
            $this->getDescription(),
            $this->getSortedPlugins(),
            array_map(function (Field $field) {
                return $field->getSignature();
            }, $fields ?? []),
        ];

        return md5(json_encode($components) ?? '');
    }
}
