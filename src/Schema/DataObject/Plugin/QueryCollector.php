<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use Generator;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class QueryCollector
{
    use Injectable;

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return Generator
     * @throws SchemaBuilderException
     */
    public function collectQueries(): Generator
    {
        foreach ($this->schema->getQueryType()->getFields() as $field) {
            if ($field instanceof ModelQuery) {
                yield $field;
            }
        }
        foreach ($this->schema->getModels() as $model) {
            foreach ($model->getFields() as $field) {
                if ($field instanceof ModelField && $field->getModelType()) {
                    yield $field;
                }
            }
        }
        foreach ($this->schema->getInterfaces() as $interface) {
            if (!$interface instanceof ModelInterfaceType) {
                continue;
            }
            foreach ($interface->getFields() as $field) {
                if ($field instanceof ModelField && $field->getModelType()) {
                    yield $field;
                }
            }
        }
    }

    /**
     * @param ModelType $type
     * @return Generator
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
