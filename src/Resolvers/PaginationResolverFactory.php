<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use Closure;
use InvalidArgumentException;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\ORM\SS_List;
use Exception;

class PaginationResolverFactory implements ResolverFactory, CodeGenerator
{
    use Injectable;

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
     * @param callable|ResolverFactory $parentResolver
     * @param $defaultLimit
     * @param $maximumLimit
     * @param array $sortableFields
     */
    public function __construct($parentResolver, $defaultLimit, $maximumLimit, $sortableFields = [])
    {
        if (
            $parentResolver &&
            !$parentResolver instanceof ResolverFactory &&
            (!is_callable($parentResolver) || $parentResolver instanceof Closure)
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s must be passed a resolver using the callable array syntax. Closures are not allowed',
                __CLASS__
            ));
        }

        $this->parentResolver = $parentResolver;
        $this->defaultLimit = $defaultLimit;
        $this->maximumLimit = $maximumLimit;
        $this->sortableFields = $sortableFields;
    }

    /**
     * @return Closure
     */
    public function createResolver()
    {
        return function ($obj, array $args, $context, ResolveInfo $info) {
            $func = $this->parentResolver instanceof ResolverFactory
                ? $this->parentResolver->createResolver()
                : $this->parentResolver;
            $list = call_user_func_array($func, func_get_args());
            if (!$list instanceof SS_List) {
                throw new Exception('Connection::resolve() must resolve to a SS_List instance.');
            }
            $args = [
                $list,
                $args,
                $this->defaultLimit,
                $this->maximumLimit,
                $this->sortableFields,
            ];
            return Connection::resolveList(...$args);
        };
    }

    /**
     * @return string
     */
    public function toCode()
    {
        $resolverCode = $this->parentResolver instanceof CodeGenerator
            ? $this->parentResolver->toCode()
            : var_export($this->parentResolver, true);
        return sprintf(
            'new %s(%s, %s, %s, %s)',
            __CLASS__,
            $resolverCode,
            $this->defaultLimit,
            $this->maximumLimit,
            var_export($this->sortableFields, true)
        );
    }
}