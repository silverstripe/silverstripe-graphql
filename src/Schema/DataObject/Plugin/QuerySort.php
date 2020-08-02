<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use SilverStripe\GraphQL\QueryFilter\FilterRegistryInterface;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQuerySortPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Closure;

class QuerySort extends AbstractQuerySortPlugin
{
    const IDENTIFIER = 'dataobjectQuerySort';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @return array
     */
    protected function getResolver(): array
    {
        return [static::class, 'sort'];
    }

    /**
     * @param Query $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(Query $query, Schema $schema, array $config = []): void
    {
        Schema::invariant(
            $query instanceof ModelQuery &&
            is_subclass_of(
                $query->getModel()->getSourceClass(),
                DataObject::class
            ),
            'Cannot apply plugin %s to a query that is not based on a DataObject'
        );
        parent::apply($query, $schema, $config);
    }

    /**
     * @param ModelType $modelType
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(ModelType $modelType): array
    {
        $filters = [];
        /* @var ModelField $fieldObj */
        foreach ($modelType->getFields() as $fieldObj) {
            $fieldName = $fieldObj->getPropertyName();
            if (!$modelType->getModel()->hasField($fieldName)) {
                continue;
            }
            // Plural relationships are not sortable. No nested lists allowed.
            if (!$fieldObj->isList() && $relatedModel = $fieldObj->getModelType()) {
                $filters[$fieldObj->getPropertyName()] = $this->buildAllFieldsConfig($relatedModel);
            } else {
                $filters[$fieldObj->getName()] = true;
            }
        }

        return $filters;
    }


    /**
     * @param string $class
     * @param string $fieldName
     * @return string
     */
    protected static function getObjectProperty(string $class, string $fieldName): string
    {
        $sng = DataObject::singleton($class);
        return FieldAccessor::singleton()->normaliseField($sng, $fieldName) ?: $fieldName;
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function sort(array $context)
    {
        $mapping = $context['fieldMapping'] ?? [];
        $fieldName = $context['fieldName'];

        return function (DataList $list, array $args) use ($mapping, $fieldName) {
            $filterArgs = $args[$fieldName] ?? [];
            /* @var FilterRegistryInterface $registry */
            $paths = static::buildPathsFromArgs($filterArgs);
            foreach ($paths as $path => $value) {
                $normalised = $mapping[$path] ?? $path;
                $list = $list->sort($normalised, $value);
            }

            return $list;
        };
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
