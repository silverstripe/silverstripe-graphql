<?php


namespace SilverStripe\GraphQL\Schema;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\Scalar;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\UnionType;

/**
 * A readonly value object that represents a schema in its final consumable
 * state. It knows nothing about models or operations, plugins,
 * or all the abstractions surrounding how the schema gets defined
 *
 * {@link SchemaStorageInterface} expects to deal with an instance of this
 * rather than a {@link Schema} instance, which is more of a sandbox whose
 * state cannot be trusted at any given time.
 */
class StorableSchema implements SchemaValidator
{
    use Injectable;

    /**
     * @var Type[]
     */
    private array $types;

    /**
     * @var Enum[]
     */
    private array $enums;

    /**
     * @var InterfaceType[]
     */
    private array $interfaces;

    /**
     * @var UnionType[]
     */
    private array $unions;

    /**
     * @var Scalar[]
     */
    private array $scalars;

    private SchemaConfig $config;

    public function __construct(array $config = [], ?SchemaConfig $context = null)
    {
        $this->types = $config[Schema::TYPES] ?? [];
        $this->enums = $config[Schema::ENUMS] ?? [];
        $this->interfaces = $config[Schema::INTERFACES] ?? [];
        $this->unions = $config[Schema::UNIONS] ?? [];
        $this->scalars = $config[Schema::SCALARS] ?? [];
        $this->config = $context ?: SchemaConfig::create();
    }

    /**
     * @return Types[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return Enum[]
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    /**
     * @return InterfaceType[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return UnionType[]
     */
    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * @return Scalar[]
     */
    public function getScalars(): array
    {
        return $this->scalars;
    }

    public function getConfig(): SchemaConfig
    {
        return $this->config;
    }

    public function exists(): bool
    {
        $queryType = $this->types[Schema::QUERY_TYPE] ?? null;
        if (!$queryType) {
            return false;
        }
        $fields = $queryType->getFields();
        if (empty($fields)) {
            return false;
        }
        $otherTypes = array_filter(array_keys($this->types ?? []), function ($key) {
            return $key !== Schema::QUERY_TYPE;
        });
        if (empty($otherTypes)) {
            return false;
        }

        return true;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        $allNames = array_merge(
            array_keys($this->types ?? []),
            array_keys($this->enums ?? []),
            array_keys($this->interfaces ?? []),
            array_keys($this->unions ?? []),
            array_keys($this->scalars ?? [])
        );
        $dupes = [];
        foreach (array_count_values($allNames ?? []) as $val => $count) {
            if ($count > 1) {
                $dupes[] = $val;
            }
        }

        Schema::invariant(
            empty($dupes),
            'Your schema has multiple types with the same name. See %s',
            implode(', ', $dupes)
        );

        $queryType = $this->types[Schema::QUERY_TYPE] ?? null;
        $mutationType = $this->types[Schema::MUTATION_TYPE] ?? null;

        Schema::invariant(
            !empty($this->types) && $queryType,
            'Your schema must contain at least one type and at least one query'
        );

        $queryFields = $queryType->getFields();
        $mutationFields = $mutationType ? $mutationType->getFields() : [];

        $validators = array_merge(
            $this->types,
            $queryFields,
            $mutationFields,
            $this->enums,
            $this->interfaces,
            $this->unions,
            $this->scalars
        );
        /* @var SchemaValidator $validator */
        foreach ($validators as $validator) {
            $validator->validate();
        }
    }
}
