<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use GraphQL\Type\Definition\ResolveInfo;
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

    protected function getResolver(array $config): callable
    {
        return [static::class, 'filter'];
    }

    /**
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

    protected function updateInputBuilder(NestedInputBuilder $builder): void
    {
        parent::updateInputBuilder($builder);
        $builder->setFieldFilter(function (Type $type, Field $field) {
            if (!$type instanceof ModelType) {
                return false;
            }

            $dataObject = DataObject::singleton($type->getModel()->getSourceClass());
            $fieldName = $field instanceof ModelField ? $field->getPropertyName() : $field->getName();
            $isNative = FieldAccessor::singleton()->hasNativeField($dataObject, $fieldName);

            // If the field has its own resolver, then we'll allow anything it because the user is
            // handling all the computation.
            return $isNative || $field->getResolver();
        });
    }

    public static function filter(array $context): Closure
    {
        $fieldName = $context['fieldName'];
        $rootType = $context['rootType'];
        $resolvers = $context['resolvers'] ?? [];

        return function (?Filterable $list, array $args, array $context, ResolveInfo $info) use ($fieldName, $rootType, $resolvers) {
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
            if (empty($filterArgs)) {
                return $list;
            }
            /* @var FilterRegistryInterface $registry */
            $registry = Injector::inst()->get(FilterRegistryInterface::class);
            $paths = NestedInputBuilder::buildPathsFromArgs($filterArgs);
            foreach ($paths as $path => $value) {
                $fieldParts = explode('.', $path ?? '');
                $filterID = array_pop($fieldParts);
                $fieldPath = implode('.', $fieldParts);
                $filter = $registry->getFilterByIdentifier($filterID);
                Schema::invariant(
                    $filter,
                    'No registered filters match the identifier "%s". Did you register it with %s?',
                    $filterID,
                    FilterRegistryInterface::class
                );
                if (isset($resolvers[$fieldPath])) {
                    $newContext = $context;
                    $newContext['filterComparator'] = $filterID;
                    $newContext['filterValue'] = $value;
                    $list = call_user_func_array($resolvers[$fieldPath], [$list, $args, $newContext, $info]);
                    continue;
                }
                $normalised = $schemaContext->mapPath($rootType, $fieldPath);
                Schema::invariant(
                    $normalised,
                    'Plugin %s could not map path %s on %s. If this is a custom filter field, make sure you included
                    a resolver.',
                    static::IDENTIFIER,
                    $fieldPath,
                    $rootType
                );
                if ($filter) {
                    $list = $filter->apply($list, $normalised, $value);
                }
            }

            return $list;
        };
    }
}
