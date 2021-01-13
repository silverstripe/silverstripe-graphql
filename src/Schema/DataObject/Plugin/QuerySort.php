<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQuerySortPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Closure;
use SilverStripe\ORM\Sortable;

/**
 * Adds a sort parameter to a DataObject query
 */
class QuerySort extends AbstractQuerySortPlugin
{
    const IDENTIFIER = 'sort';

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
    protected function getResolver(array $config): array
    {
        return [static::class, 'sort'];
    }

    /**
     * @param ModelField $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelField $query, Schema $schema, array $config = []): void
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
     * @param string $class
     * @param string $fieldName
     * @param Schema $schema
     * @return string
     */
    protected static function getObjectProperty(string $class, string $fieldName, Schema $schema): string
    {
        $modelType = $schema->getModelByClassName($class);
        if ($modelType) {
            /* @var ModelField $field */
            $field = $modelType->getFieldByName($fieldName);
            if ($field) {
                $prop = $field->getPropertyName();
                $sng = DataObject::singleton($class);
                return FieldAccessor::singleton()->normaliseField($sng, $prop) ?: $fieldName;
            }
        }

        return $fieldName;
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function sort(array $context): closure
    {
        $mapping = $context['fieldMapping'] ?? [];
        $fieldName = $context['fieldName'];

        return function (?Sortable $list, array $args) use ($mapping, $fieldName) {
            if ($list === null) {
                return null;
            }
            $filterArgs = $args[$fieldName] ?? [];
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
