<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\Scaffolding\ResolverInterface;
use SilverStripe\GraphQL\Manager;

/**
 * Injects Operation creator features into mutations and queries, which are different inheritance chains
 * @package SilverStripe\GraphQL\Scaffolding\Operations
 */
trait OperationCreatorTrait
{
    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var \Closure|SilverStripe\GraphQL\ResolverInterface
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $operationName;

    /**
     * @var array
     */
    protected $argsMap = [];

    /**
     * OperationCreatorTrait constructor.
     * @param Manager $manager
     * @param $operationName
     * @param $typeName
     * @param null $resolver
     * @param array $argsMap
     */
    public function __construct(Manager $manager, $operationName, $typeName, $resolver = null, $argsMap = [])
    {
        $this->typeName = $typeName;
        $this->resolver = $resolver;
        $this->operationName = $operationName;
        $this->argsMap = $argsMap;

        parent::__construct($manager);
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => $this->operationName
        ];
    }

    /**
     * @return array
     */
    public function args()
    {
        return $this->argsMap;
    }

    /**
     * Overload the getResolver() method to be aware of the ResolverInterface option
     * @return mixed
     */
    protected function getResolver()
    {
        $resolver = $this->resolver;

        return function () use ($resolver) {
            $args = func_get_args();
            if (is_callable($resolver)) {
                return call_user_func_array($resolver, $args);
            } else {
                if ($resolver instanceof ResolverInterface) {
                    return call_user_func_array([$resolver, 'resolve'], $args);
                } else {
                    throw new \Exception(sprintf(
                        '%s resolver must be a closure or implement %s',
                        __CLASS__,
                        ResolverInterface::class
                    ));
                }
            }
        };
    }

}