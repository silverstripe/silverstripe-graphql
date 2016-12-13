<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\Core\Object;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Sortable;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\TypeCreator;
use SilverStripe\GraphQL\FieldCreator;
use SilverStripe\GraphQL\Pagination\PageInfoType;
use SilverStripe\GraphQL\Pagination\SortDirectionType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * A connection to a list of items on a object type. Collections are paginated
 * and return a list of edges.
 *
 * <code>
 *  friends(limit:2,offset:2) {
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
class Connection extends Object
{
    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var ObjectType
     */
    protected $connectedType;

    /**
     * @var string
     */
    protected $connectionDescription;

    /**
     * @var callable
     */
    protected $connectionResolver;

    /**
     * @var array
     */
    protected $connectionArgs = [];

    /**
     * @var array
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

    public function __construct($args)
    {
        $this->connectionName = $args['name'];
        $this->connectedType = $args['nodeType'];

        if(isset($args['resolveConnection'])) {
            $this->connectionResolver = $args['resolveConnection'];
        }

        if(isset($args['args'])) {
            $this->connectionArgs = $args['args'];
        }

        if(isset($args['description'])) {
            $this->connectionDescription = $args['description'];
        }

        if(isset($args['sortableFields'])) {
            $this->sortableFields = $args['sortableFields'];
        }

        if(isset($args['defaultLimit'])) {
            $this->defaultLimit = $args['defaultLimit'];
        }

        if(isset($args['maximumLimit'])) {
            $this->maximumLimit = $args['maximumLimit'];
        }
    }

    /**
     * Update the maximum and default limits for returned records.
     *
     * @param int $defaultLimit
     * @param int $maximumLimit
     *
     * @return $this
     */
    public function setLimits($defaultLimit, $maximumLimit = null) {
        $this->defaultLimit = $defaultLimit;
        $this->maximumLimit = $maximumLimit;

        return $this;
    }

    /**
     * Update the allowed array of sortable fields.
     *
     * @param array
     *
     * @return $this
     */
    public function setSortableFields($fields) {
        $this->sortableFields = $fields;
    }

    /**
     * Pagination support for the connection type. Currently doesn't support
     * cursors, just basic offset pagination.
     *
     * @return array
     */
    public function args()
    {
        $existing = $this->connectionArgs;

        if(!is_array($existing)) {
            $existing = [];
        }

        return array_merge($existing, [
            'limit' => [
                'type' => Type::int(),
            ],
            'offset' => [
                'type' => Type::int()
            ],
            'sort' => [
                'type' => Type::string()
            ],
            'sortDirection' => [
                'type' => Injector::inst()->get(SortDirectionType::class)->toType()
            ]
        ]);
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'pageInfo' => [
                'type' => Type::nonNull(Injector::inst()->get(PageInfoType::class)->toType()),
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
        return new ObjectType([
            'name' => $this->connectionName . 'Edge',
            'description' => 'The collections edge',
            'fields' => [
                'node' => [
                    'type' => $this->connectedType,
                    'description' => 'The node at the end of the collections edge',
                    'resolve' => function($obj) {
                        return $obj;
                    }
                ]
            ]
        ]);
    }

    /**
     * @return ObjectType
     */
    public function toType()
    {
        return new ObjectType([
            'name' => $this->connectionName . 'Connection',
            'description' => $this->connectionDescription,
            'fields' => $this->fields()
        ]);
    }

    /**
     * Returns the collection resolved with the pageInfo provided.
     *
     * @param mixed $value
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     *
     * @return array
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        $result = call_user_func_array(
            $this->connectionResolver,
            func_get_args()
        );

        if(!$result instanceof SS_List) {
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
     *
     * @return array
     */
    public function resolveList($list, $args, $context = null, $info = null) {
        $limit = (isset($args['limit']) && $args['limit']) ? $args['limit'] : $this->defaultLimit;
        $offset = (isset($args['offset'])) ? $args['offset'] : 0;

        if($limit > $this->maximumLimit) {
            $limit = $this->maximumLimit;
        }

        $nextPage = false;
        $previousPage = false;
        $count = $list->count();

        if($list instanceof Limitable) {
            $list = $list->limit($limit, $offset);

            if($limit && (($limit + $offset) < $count)) {
                $nextPage = true;
            }

            if($offset > 0) {
                $previousPage = true;
            }
        }

        if($list instanceof Sortable) {
            if(isset($args['sort'])) {
                $direction = (isset($args['sortDirection'])) ? $args['sortDirection'] : 'ASC';

                if(in_array($args['sort'], $this->sortableFields)) {
                    $list = $list->sort($args['sort'], $direction);
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
