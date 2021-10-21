<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use DomainException;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Admin\GraphQL\ReadOneLegacyResolver;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryFilter\DataObjectQueryFilter;
use SilverStripe\GraphQL\QueryFilter\QueryFilterAware;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;
use InvalidArgumentException;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class ReadOne extends ItemQueryScaffolder implements OperationResolver, CRUDInterface
{
    use QueryFilterAware;

    /**
     * Read one constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
        $filter = Injector::inst()->create(DataObjectQueryFilter::class, $dataObjectClass)
            ->setFilterKey(StaticSchema::inst()->formatField(Read::FILTER))
            ->setExcludeKey(StaticSchema::inst()->formatField(Read::EXCLUDE));
        $this->setQueryFilter($filter);
    }

    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        return 'readOne' . ucfirst($this->getTypeName());
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        $id = StaticSchema::inst()->formatField('ID');
        $args = [
            $id => [
                'type' => Type::id()
            ],
        ];
        $filterKey = StaticSchema::inst()->formatField(Read::FILTER);
        $excludeKey = StaticSchema::inst()->formatField(Read::EXCLUDE);
        if ($this->queryFilter->exists()) {
            $args[$filterKey] = [
                'type' => $this->queryFilter->getInputType($this->inputTypeName(Read::FILTER)),
            ];
            $args[$excludeKey] = [
                'type' => $this->queryFilter->getInputType($this->inputTypeName(Read::EXCLUDE)),
            ];
        }

        return $args;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function inputTypeName($key = '')
    {
        return $this->getTypeName() . $key . 'ReadOneInputType';
    }

    /**
     * @param DataObjectInterface $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws Exception
     */
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        // Throw exception if the GraphQL query and resolver do not match, see https://github.com/silverstripe/silverstripe-admin/pull/1260
        $this->checkForQueryFilterCompatibility($args);

        $id = StaticSchema::inst()->formatField('ID');
        // get as a list so extensions can influence it pre-query
        $list = DataList::create($this->getDataObjectClass());
        if (isset($args[$id])) {
            $list = $list->filter('ID', $args[$id]);
        }
        if ($this->queryFilter->exists()) {
            $list = $this->queryFilter->applyArgsToList($list, $args);
        }
        $this->extend('updateList', $list, $args, $context, $info);

        // Fall back to getting an empty singleton to use for permission checking
        $item = $list->first() ?: $this->getDataObjectInstance();

        // Check permissions on the individual item as some permission checks may investigate saved state
        $checker = $this->getPermissionChecker();
        if ($checker && !$checker->checkItem($item, $context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->getDataObjectClass()
            ));
        }

        return $list->first();
    }

    public function applyConfig(array $config)
    {
        parent::applyConfig($config);

        if (isset($config['filters'])) {
            if ($config['filters'] === SchemaScaffolder::ALL) {
                $this->queryFilter->addAllFilters();
            } else {
                if (is_array($config['filters'])) {
                    $this->queryFilter->applyConfig($config['filters']);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Config setting "filters" must be an array mapping field names to a list of filter identifiers, or %s for all',
                        SchemaScaffolder::ALL
                    ));
                }
            }
        }
    }

    /**
     * Whether or not the query filter is compatible with the current resolver.
     *
     * @throw DomainException
     */
    protected function checkForQueryFilterCompatibility(array $args): void
    {
        // Skip implementation if query filter is compatible
        if (!array_key_exists('filter', $args)) {
            return;
        }

        // Add message to exception if the readone legacy resolver does not exists in admin module
        $missingResolver = '';
        if (!class_exists(ReadOneLegacyResolver::class)) {
            $missingResolver = 'Note: The legacy data object resolver does not exist in the used version of silverstripe/admin.';
        }

        throw new DomainException(sprintf(
            'Hey, it looks like you\'ve got graphql 4 style filters and you\'re using the graphql 3 resolver for object (%s). ' .
            'You can use the legacy data object resolver to fix this issue (%s). %s',
            $this->getDataObjectClass(),
            'SilverStripe\\Admin\\GraphQ\\ReadOneLegacyResolver',
            $missingResolver
        ));
    }
}
