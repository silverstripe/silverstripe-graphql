<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactory;
use Closure;
use InvalidArgumentException;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;
use SilverStripe\GraphQL\Storage\Encode\Helpers;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
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
        if (!isset($context['parentResolver']) || !$context['parentResolver'] instanceof ResolverAbstraction) {
            throw new InvalidArgumentException(sprintf(
                '%s constructor must be passed a %s instance as the parentResolver setting',
                __CLASS__,
                ResolverAbstraction::class
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
            /* @var ResolverAbstraction $resolver */
            $resolver = $this->config['parentResolver'];
            $func = $resolver->export()->createClosure();
            $list = call_user_func_array($func, func_get_args());
            if (!$list instanceof SS_List) {
                throw new Exception('Connection::resolve() must resolve to a SS_List instance.');
            }
            $args = [
                $list,
                $args,
                $this->config['defaultLimit'],
                $this->config['maximumLimit'],
                $this->config['sortableFields'],
            ];
            return Connection::resolveList(...$args);
        };
    }

    protected function getContextExpression()
    {
        $context = $this->context;
        if (isset($context['parentResolverFactory'])) {
            $context['parentResolver'] = $context['parentResolverFactory']->getExpression();
            unset($context['parentResolverFactory']);
        }

        return Helpers::normaliseValue($context);
    }

}