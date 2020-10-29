<?php

namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\Limitable;
use SilverStripe\GraphQL\Schema\Plugin\PaginationPlugin;
use Closure;

/**
 * Adds pagination to a DataList query
 */
class Paginator extends PaginationPlugin
{
    const IDENTIFIER = 'paginateList';

    private static $resolver = [__CLASS__, 'paginate'];

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
            if (!$list instanceof Limitable) {
                return static::createPaginationResult($list, $list, $maxLimit, 0);
            }

            $offset = $args['offset'];
            $limit = $args['limit'];
            $total = $list->count();

            $limit = min($limit, $maxLimit);

            // Apply limit
            /* @var Limitable $list */
            $limitedList = $list->limit($limit, $offset);
            return static::createPaginationResult($total, $limitedList, $limit, $offset);
        };
    }
}
