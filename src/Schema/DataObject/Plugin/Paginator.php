<?php

namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\Limitable;
use SilverStripe\GraphQL\Schema\Plugin\AbstractPaginationPlugin;
use Closure;

class Paginator extends AbstractPaginationPlugin
{
    const IDENTIFIER = 'dataobjectPaginator';

    /**
     * @return array
     */
    protected function getPaginationResolver()
    {
        return [static::class, 'paginate'];
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function paginate(array $context): Closure
    {
        $maxLimit = $context['maxLimit'];

        return function ($list, array $args, array $context, ResolveInfo $info) use ($maxLimit) {
            // Default values
            $count = $list->count();
            $nextPage = false;
            $previousPage = false;
            // If list is limitable, apply pagination
            /* @var Limitable $list */
            if ($list instanceof Limitable) {
                $offset = $args['offset'];
                $limit = $args['limit'];
                if ($limit > $maxLimit) {
                    $limit = $maxLimit;
                }

                // Apply limit
                $list = $list->limit($limit, $offset);

                // Flag prev-next page
                if ($limit && (($limit + $offset) < $count)) {
                    $nextPage = true;
                }
                if ($offset > 0) {
                    $previousPage = true;
                }
            }

            return [
                'edges' => $list,
                'nodes' => $list,
                'pageInfo' => [
                    'totalCount' => $count,
                    'hasNextPage' => $nextPage,
                    'hasPreviousPage' => $previousPage
                ]
            ];
        };
    }

}
