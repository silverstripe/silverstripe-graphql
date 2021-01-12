<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQueryFilterPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Closure;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\SS_List;

/**
 * Adds a filter parameter to a DataObject query
 */
class QueryFilter extends AbstractQueryFilterPlugin
{
    const IDENTIFIER = 'filter';

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
        return [static::class, 'filter'];
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
            'Cannot apply plugin %s to a query that is not based on a DataObject'
        );
        parent::apply($query, $schema, $config);
    }

    /**
     * @param string $class
     * @param string $fieldName
     * @param Schema $schema
     * @return string|null
     */
    public static function getObjectProperty(string $class, string $fieldName, Schema $schema): string
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
    public static function filter(array $context)
    {
        $mapping = $context['fieldMapping'] ?? [];
        $fieldName = $context['fieldName'];

        return function (?Filterable $list, array $args) use ($mapping, $fieldName) {
            if (!$list) {
                return $list;
            }
            $filterArgs = $args[$fieldName] ?? [];
            /* @var FilterRegistryInterface $registry */
            $registry = Injector::inst()->get(FilterRegistryInterface::class);
            $paths = static::buildPathsFromArgs($filterArgs);
            foreach ($paths as $path => $value) {
                $fieldParts = explode('.', $path);
                $filterID = array_pop($fieldParts);
                $fieldPath = implode('.', $fieldParts);
                $normalised = $mapping[$fieldPath] ?? $fieldPath;
                $filter = $registry->getFilterByIdentifier($filterID);
                if ($filter) {
                    $list = $filter->apply($list, $normalised, $value);
                }
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
        $fieldName = $field->getPropertyName();
        return stristr($fieldName, '.') === false;
    }
}
