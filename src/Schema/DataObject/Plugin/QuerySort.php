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
use GraphQL\Type\Definition\ResolveInfo;

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
        return function (?Sortable $list, array $args, array $context, ResolveInfo $info) use ($fieldName, $rootType) {
            if ($list === null) {
                return null;
            }

            if (!isset($args[$fieldName])) {
                return $list;
            }

            $sortArgs = static::getSortArgs($info, $args, $rootType, $fieldName);
            $paths = NestedInputBuilder::buildPathsFromArgs($sortArgs);
            if (empty($paths)) {
                return $list;
            }

            $schemaContext = SchemaConfigProvider::get($context);
            if (!$schemaContext) {
                throw new Exception(sprintf(
                    'No schemaContext was present in the resolver context. Make sure the %s class is added
                    to the query handler',
                    SchemaConfigProvider::class
                ));
            }

            $normalisedPaths = [];
            foreach ($paths as $path => $value) {
                $normalised = $schemaContext->mapPath($rootType, $path);
                Schema::invariant(
                    $normalised,
                    'Plugin %s could not map path %s on %s',
                    static::IDENTIFIER,
                    $path,
                    $rootType
                );

                $normalisedPaths[$normalised] = $value;
            }

            return $list->sort($normalisedPaths);
        };
    }

    private static function getSortArgs(ResolveInfo $info, array $args, string $rootType, string $fieldName): array
    {
        $sortArgs = [];
        $sortOrder = self::getSortOrder($info, $rootType, $fieldName);

        foreach ($sortOrder as $orderName) {
            if (!isset($args[$fieldName][$orderName])) {
                continue;
            }
            $sortArgs[$orderName] = $args[$fieldName][$orderName];
            unset($args[$fieldName][$orderName]);
        }

        return array_merge($sortArgs, $args[$fieldName]);
    }

    /**
     * Gets the original order of fields to be sorted based on the query args order.
     *
     * This is necessary because the underlying GraphQL implementation we're using ignores the
     * order of query args, and uses the order that fields are defined in the schema instead.
     */
    private static function getSortOrder(ResolveInfo $info, string $rootType, string $fieldName)
    {
        // If we don't have the right field definition, just use the existing order
        if ($info->fieldDefinition->getType()->name ?? '' === $rootType) {
            $relevantNode = $info->fieldDefinition->getName();

            // Find the query field node that matches the schema
            foreach ($info->fieldNodes as $node) {
                if ($node->name->value !== $relevantNode) {
                    continue;
                }

                // Find the sort arg
                foreach ($node->arguments as $arg) {
                    if ($arg->name->value !== $fieldName) {
                        continue;
                    }

                    // Get the sort order from the query
                    $sortOrder = [];
                    foreach ($arg->value->fields as $field) {
                        $sortOrder[] = $field->name->value;
                    }
                    return $sortOrder;
                }
            }
        }
        return [];
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
