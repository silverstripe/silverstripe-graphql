<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Storage\Encode\ResolverFactory;
use Closure;
use InvalidArgumentException;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\ORM\SS_List;
use Exception;

class PaginationResolverFactory extends ResolverFactory
{

    /**
     * @var callable
     */
    protected $parentResolver;

    /**
     * @var int
     */
    protected $defaultLimit;

    /**
     * @var int
     */
    protected $maximumLimit;

    /**
     * @var array
     */
    protected $sortableFields = [];

    /**
     * PaginationResolverFactory constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        if (
            isset($context['parentResolver']) &&
            !$context['parentResolver'] instanceof ResolverFactory &&
            (!is_callable($context['parentResolver']) || $context['parentResolver'] instanceof Closure)
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s must be passed a resolver using the callable array syntax. Closures are not allowed',
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
     * @param TypeRegistryInterface $registry
     * @return callable|Closure
     */
    public function createResolver(TypeRegistryInterface $registry)
    {
        return function ($obj, array $args, $context, ResolveInfo $info) use ($registry) {
            $func = $this->config['parentResolver'] instanceof ResolverFactory
                ? $this->config['parentResolver']->createResolver($registry)
                : $this->config['parentResolver'];
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

}