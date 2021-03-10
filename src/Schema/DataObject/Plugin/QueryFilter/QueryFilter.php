<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Plugin\AbstractQueryFilterPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;
use Closure;
use SilverStripe\ORM\Filterable;
use Exception;

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
     * @param array $context
     * @return Closure
     */
    public static function filter(array $context)
    {
        $fieldName = $context['fieldName'];
        $rootType = $context['rootType'];

        return function (?Filterable $list, array $args, array $context) use ($fieldName, $rootType) {
            if ($list === null) {
                return null;
            }
            $schemaContext = SchemaConfigProvider::get($context);
            if (!$schemaContext) {
                throw new Exception(sprintf(
                    'No schemaContext was present in the resolver context. Make sure the %s class is added
                    to the query handler',
                    SchemaConfigProvider::class
                ));
            }
            $filterArgs = $args[$fieldName] ?? [];
            /* @var FilterRegistryInterface $registry */
            $registry = Injector::inst()->get(FilterRegistryInterface::class);
            $paths = NestedInputBuilder::buildPathsFromArgs($filterArgs);
            foreach ($paths as $path => $value) {
                $fieldParts = explode('.', $path);
                $filterID = array_pop($fieldParts);
                $fieldPath = implode('.', $fieldParts);

                $normalised = $schemaContext->mapPath($rootType, $fieldPath);
                Schema::invariant(
                    $normalised,
                    'Plugin %s could not map path %s on %s',
                    static::IDENTIFIER,
                    $fieldPath,
                    $rootType
                );
                $filter = $registry->getFilterByIdentifier($filterID);
                if ($filter) {
                    $list = $filter->apply($list, $normalised, $value);
                }
            }

            return $list;
        };
    }
}
