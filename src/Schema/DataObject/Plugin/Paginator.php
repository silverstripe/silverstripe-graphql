<?php

namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\Limitable;
use SilverStripe\GraphQL\Schema\Plugin\PaginationPlugin;
use Closure;

/**
 * Adds pagination to a DataList query
 */
class Paginator extends PaginationPlugin
{
    const IDENTIFIER = 'paginateList';

    /**
     * @var callable
     * @config
     */
    private static $resolver = [__CLASS__, 'paginate'];

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public static function paginate(array $context): Closure
    {
        $maxLimit = $context['maxLimit'];

        return function ($list, array $args, array $context, ResolveInfo $info) use ($maxLimit) {
            if ($list === null) {
                return null;
            }

            if (!$list instanceof Limitable) {
                Schema::invariant(
                    !isset($list['nodes']),
                    'List on field %s has already been paginated. Was the plugin executed twice?',
                    $info->fieldName
                );
                return static::createPaginationResult(count($list ?? []), $list, $maxLimit, 0);
            }

            $total = $list->count();
            $offset = $args['offset'];
            $limit = $args['limit'];

            $limit = min($limit, $maxLimit);

            // Apply limit
            /* @var Limitable $list */
            $limitedList = $list->limit($limit, $offset);
            return static::createPaginationResult($total, $limitedList, $limit, $offset);
        };
    }
}
