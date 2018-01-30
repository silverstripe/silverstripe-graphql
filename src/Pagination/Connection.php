<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\SS_List;

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
     * {@link ObjectType}.
     *
     * @var ObjectType|Callable
     */
    protected $connectedType;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Callable
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
     * @param string $connectionName
     */
    public function __construct($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @param Callable
     *
     * @return $this
     */
    public function setConnectionResolver($func)
    {
        $this->connectionResolver = $func;

        return $this;
    }

    /**
     * Pass in the {@link ObjectType}.
     *
     * @param ObjectType|Callable $type Type, or callable to evaluate type
     * @return $this
     */
    public function setConnectionType($type)
    {
        $this->connectedType = $type;

        return $this;
    }

    /**
     * Evaluate Connection type
     *
     * @param bool $evaluate
     * @return ObjectType|Callable
     */
    public function getConnectionType($evaluate = true)
    {
        return ($evaluate && is_callable($this->connectedType))
            ? call_user_func($this->connectedType)
            : $this->connectedType;
    }

    /**
     * @return Callable
     */
    public function getConnectionResolver()
    {
        return $this->connectionResolver;
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
            'limit' => [
                'type' => Type::int(),
            ],
            'offset' => [
                'type' => Type::int()
            ]
        ]);

        if ($fields = $this->getSortableFields()) {
            $args['sortBy'] = [
                'type' => Type::listOf(
                    Injector::inst()->create(SortInputTypeCreator::class, $this->connectionName)
                        ->setSortableFields($fields)
                        ->toType()
                )
            ];
        }

        return $args;
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'pageInfo' => [
                'type' => Type::nonNull(
                    Injector::inst()->get(PageInfoTypeCreator::class)->toType()
                ),
                'description' => 'Pagination information'
            ],
            'edges' => [
                'type' => Type::listOf($this->getEdgeType()),
                'description' => 'Collection of records'
            ]
        ];
    }

    /**
     * @return ObjectType
     */
    public function getEdgeType()
    {
        if (!$this->connectedType) {
            throw new InvalidArgumentException('Missing connectedType callable');
        }

        return new ObjectType([
            'name' => $this->getEdgeTypeName(),
            'description' => 'The collections edge',
            'fields' => function () {
                return [
                    'node' => [
                        'type' => $this->getConnectionType(),
                        'description' => 'The node at the end of the collections edge',
                        'resolve' => function ($obj) {
                            return $obj;
                        }
                    ]
                ];
            }
        ]);
    }

    /**
     * @return ObjectType
     */
    public function toType()
    {
        return new ObjectType([
            'name' => $this->getConnectionTypeName(),
            'description' => $this->description,
            'fields' => function () {
                return $this->fields();
            },
        ]);
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
        $result = call_user_func_array(
            $this->connectionResolver,
            func_get_args()
        );

        if (!$result instanceof SS_List) {
            throw new \Exception('Connection::resolve() must resolve to a SS_List instance.');
        }

        return $this->resolveList($result, $args, $context, $info);
    }

    /**
     * Wraps an {@link SS_List} with the required data in order to return it as
     * a response. If you wish to resolve a standard array as a list use
     * {@link ArrayList}.
     *
     * @param SS_List $list
     * @param array $args
     * @param null $context
     * @param ResolveInfo $info
     * @return array
     */
    public function resolveList($list, array $args, $context = null, ResolveInfo $info = null)
    {
        $limit = (isset($args['limit']) && $args['limit']) ? $args['limit'] : $this->defaultLimit;
        $offset = (isset($args['offset'])) ? $args['offset'] : 0;

        if ($limit > $this->maximumLimit) {
            $limit = $this->maximumLimit;
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
            $sortableFields = $this->getSortableFields();
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
}
