<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Schema\Components\Argument;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\InternalType;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;
use SilverStripe\GraphQL\Schema\Components\TypeReference;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\SS_List;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A connection to a list of items on a object type. Collections are paginated
 * and return a list of edges.
 *
 * <code>
 *  friends(limit:2,offset:2,sortBy:[{field:Name,direction:ASC}]) {
 *     edges {
 *       node {
 *         name
 *       }
 *     }
 *     pageInfo {
 *       totalCount
 *       hasPreviousPage
 *       hasNextPage
 *     }
 *   }
 * </code>
 */
class Connection implements OperationResolver
{
    use Injectable;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * Return a thunk function, which in turn returns the lazy-evaluated
     *
     * @var \SilverStripe\GraphQL\Schema\Components\TypeReference
     */
    protected $connectedType;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var AbstractFunction
     */
    protected $connectionResolver;

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var array Keyed by field argument name, values as DataObject column names.
     * Does not support in-memory sorting for composite values (getters).
     */
    protected $sortableFields = [];

    /**
     * @var int
     */
    protected $defaultLimit = 100;

    /**
     * The maximum limit supported for the connection. Used to prevent excessive
     * load on the server. To override the default limit, use {@link setLimits}
     *
     * @var int
     */
    protected $maximumLimit = 100;

    /**
     * @var ObjectType
     */
    protected $edgeType;

    /**
     * The simple resolver that simply passes on the object
     * @param $obj
     * @return mixed
     */
    public static function nodeResolver($obj)
    {
        return $obj;
    }

    /**
     * @param string $connectionName
     */
    public function __construct($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @param AbstractFunction $resolver
     *
     * @return $this
     */
    public function setConnectionResolver(AbstractFunction $resolver)
    {
        $this->connectionResolver = $resolver;

        return $this;
    }

    /**
     * @return AbstractFunction
     */
    public function getConnectionResolver()
    {
        return $this->connectionResolver;
    }


    /**
     * Pass in the {@link ObjectType}.
     *
     * @param TypeReference $type
     * @return $this
     */
    public function setConnectionType(TypeReference $type)
    {
        $this->connectedType = $type;

        return $this;
    }

    /**
     * Evaluate Connection type
     *
     * @return TypeReference
     */
    public function getConnectionType()
    {
        return $this->connectedType;
    }

    /**
     * @param array|Callable
     *
     * @return $this
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param string
     *
     * @return $this
     */
    public function setDescription($string)
    {
        $this->description = $string;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $fields See {@link $sortableFields}
     * @return $this
     */
    public function setSortableFields($fields)
    {
        foreach ($fields as $field => $lookup) {
            $this->sortableFields[is_numeric($field) ? $lookup : $field] = $lookup;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getSortableFields()
    {
        return $this->sortableFields;
    }

    /**
     * @param int
     *
     * @return $this
     */
    public function setDefaultLimit($limit)
    {
        $this->defaultLimit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * @param int
     *
     * @return $this
     */
    public function setMaximumLimit($limit)
    {
        $this->maximumLimit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaximumLimit()
    {
        return $this->maximumLimit;
    }

    /**
     * @return string
     */
    public function getConnectionTypeName()
    {
        return $this->connectionName . 'Connection';
    }

    /**
     * @return string
     */
    public function getEdgeTypeName()
    {
        return $this->connectionName . 'Edge';
    }

    /**
     * @return SortInputTypeCreator
     */
    public function getSortTypeCreator()
    {
        return Injector::inst()->create(SortInputTypeCreator::class, $this->connectionName)
            ->setSortableFields($this->getSortableFields());
    }

    /**
     * @return FieldCollection
     * @throws NotFoundExceptionInterface
     */
    public function getPageInfoType()
    {
        return Injector::inst()->get(PageInfoTypeCreator::class)->toType();
    }

    /**
     * Pagination support for the connection type. Currently doesn't support
     * cursors, just basic offset pagination.
     *
     * @return array
     */
    public function args()
    {
        $existing = is_callable($this->args) ? call_user_func($this->args) : $this->args;

        if (!is_array($existing)) {
            $existing = [];
        }

        $args = array_merge($existing, [
            new Argument('limit', InternalType::int()),
            new Argument('offset', InternalType::int())
        ]);

        if ($this->getSortableFields()) {
            $args[] = new Argument(
                'type',
                TypeReference::create($this->getSortTypeCreator()->getName())
                    ->setList(true)
            );
        }

        return $args;
    }

    /**
     * @return array
     * @throws NotFoundExceptionInterface
     */
    public function fields()
    {
        return [
            Field::create(
                'pageInfo',
                TypeReference::create($this->getPageInfoType()->getName())
                    ->setRequired(true)
            )->setDescription('Pagination information'),
            Field::create(
                'edges',
                TypeReference::create($this->getEdgeTypeName())
                    ->setList(true)
            )->setDescription('Collection of records'),
        ];
    }

    /**
     * @return FieldCollection
     */
    public function getEdgeType()
    {
        if (!$this->connectedType) {
            throw new InvalidArgumentException('Missing connectedType callable');
        }

        if (!$this->edgeType) {
            $this->edgeType = new FieldCollection(
                $this->getEdgeTypeName(),
                'The collections edge',
                [
                    Field::create(
                        'node',
                        TypeReference::create($this->getConnectionType()->getName()),
                        new StaticFunction([static::class, 'nodeResolver'])
                    )->setDescription('The node at the end of the collections edge')
                ]
            );
        }

        return $this->edgeType;
    }

    /**
     * @return FieldCollection
     */
    public function toType()
    {
        return new FieldCollection(
            $this->getConnectionTypeName(),
            $this->description,
            $this->fields()
        );
    }

    /**
     * Returns the collection resolved with the pageInfo provided.
     *
     * @param mixed $value
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return array
     * @throws \Exception
     */
    public function resolve($value, array $args, $context, ResolveInfo $info)
    {
        $func = $this->connectionResolver->export();
        $result = call_user_func_array($func, func_get_args());

        if (!$result instanceof SS_List) {
            throw new \Exception('Connection::resolve() must resolve to a SS_List instance.');
        }

        return $this->resolveList(
            $result,
            $args,
            $this->getDefaultLimit(),
            $this->getMaximumLimit(),
            $this->getSortableFields()
        );
    }

    /**
     * Wraps an {@link SS_List} with the required data in order to return it as
     * a response. If you wish to resolve a standard array as a list use
     * {@link ArrayList}.
     *
     * @param SS_List $list
     * @param array $args
     * @param int $defaultLimit
     * @param int $maximumLimit
     * @param array $sortableFields
     * @return array
     */
    public static function resolveList(
        $list,
        array $args,
        $defaultLimit = 100,
        $maximumLimit = 100,
        $sortableFields = []
    ) {
        $limit = (isset($args['limit']) && $args['limit']) ? $args['limit'] : $defaultLimit;
        $offset = (isset($args['offset'])) ? $args['offset'] : 0;

        if ($limit > $maximumLimit) {
            $limit = $maximumLimit;
        }

        $nextPage = false;
        $previousPage = false;
        $count = $list->count();

        if ($list instanceof Limitable) {
            $list = $list->limit($limit, $offset);

            if ($limit && (($limit + $offset) < $count)) {
                $nextPage = true;
            }

            if ($offset > 0) {
                $previousPage = true;
            }
        }

        if ($list instanceof Sortable) {
            if (isset($args['sortBy']) && !empty($args['sortBy'])) {
                // convert the input from the input format of field, direction
                // to an accepted SS_List sort format.
                // https://github.com/graphql/graphql-relay-js/issues/20#issuecomment-220494222
                $sort = [];

                foreach ($args['sortBy'] as $sortInput) {
                    $direction = (isset($sortInput['direction'])) ? $sortInput['direction'] : 'ASC';

                    if (isset($sortInput['field'])) {
                        if (!array_key_exists($sortInput['field'], $sortableFields)) {
                            throw new InvalidArgumentException(sprintf(
                                '"%s" is not a valid sort column',
                                $sortInput['field']
                            ));
                        }

                        $column = $sortableFields[$sortInput['field']];
                        $sort[$column] = $direction;
                    }
                }

                if ($sort) {
                    $list = $list->sort($sort);
                }
            }
        }

        return [
            'edges' => $list,
            'pageInfo' => [
                'totalCount' => $count,
                'hasNextPage' => $nextPage,
                'hasPreviousPage' => $previousPage
            ]
        ];
    }

    /**
     * @return AbstractType[]
     */
    public function extraTypes()
    {
        return [
            $this->getEdgeType(),
            $this->getSortTypeCreator()->toType(),
            $this->getSortTypeCreator()->getFieldType(),
            $this->getSortTypeCreator()->getSortDirectionType(),
            $this->getPageInfoType(),
        ];
    }
}
