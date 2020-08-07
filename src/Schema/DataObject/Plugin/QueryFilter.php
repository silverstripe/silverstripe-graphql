<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\QueryFilter\FilterRegistryInterface;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQueryFilterPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Closure;

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
    protected function getResolver(): array
    {
        return [static::class, 'filter'];
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
            'Cannot apply plugin %s to a query that is not based on a DataObject'
        );
        parent::apply($query, $schema, $config);
    }

    /**
     * @param string $class
     * @param string $fieldName
     * @return string|null
     */
    public static function getObjectProperty(string $class, string $fieldName): string
    {
        $sng = DataObject::singleton($class);
        return FieldAccessor::singleton()->normaliseField($sng, $fieldName) ?: $fieldName;
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function filter(array $context)
    {
        $mapping = $context['fieldMapping'] ?? [];
        $fieldName = $context['fieldName'];

        return function (DataList $list, array $args) use ($mapping, $fieldName) {
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
}
