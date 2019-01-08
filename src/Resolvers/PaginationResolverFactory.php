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
        if (!isset($context['parentResolver']) && !isset($context['parentResolverFactory'])) {
            throw new InvalidArgumentException(sprintf(
                '%s constructor must be passed a parentResolver or parentResolverFactory setting',
                __CLASS__
            ));
        }
        if (
            isset($context['parentResolver']) &&
            $context['parentResolverFactory'] instanceof Closure &&
            !isset($context['parentResolverFactory'])
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s must be passed a resolver using the callable array syntax, or use a parentResolverFactory setting.',
                __CLASS__
            ));
        }

        if (
            isset($context['parentResolverFactory']) &&
            !$context['parentResolverFactory'] instanceof ClosureFactoryInterface
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s: parentResolverFactory setting must be an instance of %s',
                __CLASS__,
                ClosureFactoryInterface::class
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
            if (isset($this->config['parentResolver'])) {
                $func = $this->config['parentResolver'];
            } else {
                /* @var ClosureFactoryInterface $factory */
                $factory = $this->config['parentResolverFactory'];
                $func = $factory->createClosure();
            }
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