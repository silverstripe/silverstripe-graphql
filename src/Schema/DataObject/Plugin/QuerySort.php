<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQuerySortPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;
use Closure;
use SilverStripe\ORM\Sortable;
use Exception;

/**
 * Adds a sort parameter to a DataObject query
 */
class QuerySort extends AbstractQuerySortPlugin
{
    const IDENTIFIER = 'sort';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getResolver(array $config): callable
    {
        return [static::class, 'sort'];
    }

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        Schema::invariant(
            is_subclass_of(
                $query->getModel()->getSourceClass(),
                DataObject::class
            ),
            'Cannot apply plugin %s to a query that is not based on a DataObject',
            $this->getIdentifier()
        );
        parent::apply($query, $schema, $config);
    }

    /**
     * @param ModelType $modelType
     * @param Schema $schema
     * @param int $level
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(ModelType $modelType, Schema $schema, int $level = 0): array
    {
        $filters = [];
        /* @var ModelField $fieldObj */
        foreach ($modelType->getFields() as $fieldObj) {
            $fieldName = $fieldObj->getPropertyName();
            if (!$modelType->getModel()->hasField($fieldName)) {
                continue;
            }
            // Plural relationships are not sortable. No nested lists allowed.
            if (!$fieldObj->isList() && $relatedModelType = $fieldObj->getModelType()) {
                if ($level > $this->config()->get('max_nesting')) {
                    continue;
                }
                if ($relatedModel = $schema->getModel($relatedModelType->getName())) {
                    $filters[$fieldObj->getPropertyName()] = $this->buildAllFieldsConfig($relatedModel, $schema, $level + 1);
                }
            } else {
                $filters[$fieldObj->getName()] = true;
            }
        }

        return $filters;
    }


    /**
     * @param array $context
     * @return Closure
     */
    public static function sort(array $context): closure
    {
        $fieldName = $context['fieldName'];
        $rootType = $context['rootType'];
        return function (?Sortable $list, array $args, array $context) use ($fieldName, $rootType) {
            if ($list === null) {
                return null;
            }
            $filterArgs = $args[$fieldName] ?? [];
            $paths = NestedInputBuilder::buildPathsFromArgs($filterArgs);
            $schemaContext = SchemaConfigProvider::get($context);
            if (!$schemaContext) {
                throw new Exception(sprintf(
                    'No schemaContext was present in the resolver context. Make sure the %s class is added
                    to the query handler',
                    SchemaConfigProvider::class
                ));
            }

            foreach ($paths as $path => $value) {
                $normalised = $schemaContext->mapPath($rootType, $path);
                Schema::invariant(
                    $normalised,
                    'Plugin %s could not map path %s on %s',
                    static::IDENTIFIER,
                    $path,
                    $rootType
                );
                $list = $list->sort($normalised, $value);
            }

            return $list;
        };
    }

    /**
     * @param NestedInputBuilder $builder
     */
    protected function updateInputBuilder(NestedInputBuilder $builder): void
    {
        parent::updateInputBuilder($builder);
        $builder->setFieldFilter(function (Type $type, Field $field) {
            if (!$type instanceof ModelType) {
                return false;
            }
            $dataObject = DataObject::singleton($type->getModel()->getSourceClass());
            $fieldName = $field instanceof ModelField ? $field->getPropertyName() : $field->getName();
            return FieldAccessor::singleton()->hasNativeField($dataObject, $fieldName);
        });
    }

    /**
     * @param ModelField $field
     * @param ModelType $modelType
     * @return bool
     */
    protected function shouldAddField(ModelField $field, ModelType $modelType): bool
    {
        return !$field->isList();
    }
}
