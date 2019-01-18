<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Schema\Encoding\Factories\ClosureFactory;
use Closure;
use InvalidArgumentException;
use SilverStripe\ORM\SS_List;
use Exception;

class PaginationResolverFactory extends ClosureFactory
{

    /**
     * PaginationResolverFactory constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        if (!isset($context['parentResolver'])) {
            throw new InvalidArgumentException(sprintf(
                '%s constructor must be passed a "parentResolver" setting',
                __CLASS__
            ));
        }
        if (!isset($context['defaultLimit'])) {
            $context['defaultLimit'] = 100;
        }
        if (!isset($context['maximumLimit'])) {
            $context['maximumLimit'] = 100;
        }
        if (!isset($context['sortableFields'])) {
            $context['sortableFields'] = [];
        }

        parent::__construct($context);
    }

    /**
     * @return callable|Closure
     */
    public function createClosure()
    {
        return function ($obj, array $args, $context, ResolveInfo $info) {
            $func = null;
            /* @var \SilverStripe\GraphQL\Schema\Components\AbstractFunction $resolver */
            $func = $this->context['parentResolver'];
            $list = call_user_func_array($func, func_get_args());
            if (!$list instanceof SS_List) {
                throw new Exception('Connection::resolve() must resolve to a SS_List instance.');
            }
            $args = [
                $list,
                $args,
                $this->context['defaultLimit'],
                $this->context['maximumLimit'],
                $this->context['sortableFields'],
            ];
            return Connection::resolveList(...$args);
        };
    }

}