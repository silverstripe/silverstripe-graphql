<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use Generator;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class QueryCollector
{
    use Injectable;

    private Schema $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function collectQueries(): array
    {
        $cached = $this->schema->getState()->get([static::class, 'queries']);
        if ($cached) {
            return $cached;
        }
        $queries = [];
        foreach ($this->schema->getQueryType()->getFields() as $field) {
            if ($field instanceof ModelQuery) {
                $queries[] = $field;
            }
        }
        foreach ($this->schema->getMutationType()->getFields() as $field) {
            if ($field instanceof ModelMutation) {
                $queries[] = $field;
            }
        }

        foreach (array_merge($this->schema->getModels(), $this->schema->getTypes()) as $type) {
            foreach ($type->getFields() as $field) {
                if ($field instanceof ModelField && $field->getModelType()) {
                    $queries[] = $field;
                }
            }
        }
        foreach ($this->schema->getInterfaces() as $interface) {
            if (!$interface instanceof ModelInterfaceType) {
                continue;
            }
            foreach ($interface->getFields() as $field) {
                if ($field instanceof ModelField && $field->getModelType()) {
                    $queries[] = $field;
                }
            }
        }
        $this->schema->getState()->set([static::class, 'queries'], $queries);

        return $queries;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function collectQueriesForType(ModelType $type): Generator
    {
        /* @var Field $query */
        foreach ($this->collectQueries() as $query) {
            if ($query->getNamedType() === $type->getName()) {
                yield $query;
            }
        }
    }
}
