<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\Manager;

/**
 * Injects Operation creator features into mutations and queries, which are different inheritance chains
 */
trait OperationCreatorTrait
{
    use PolymorphicResolverTrait;
    
    /**
     * @var string
     */
    protected $typeName;

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
     * @param string $operationName
     * @param string $typeName
     * @param SilverStripe\GraphQL\Scaffolding\ResolverInterface|\Closure $resolver
     * @param array $argsMap
     */
    public function __construct(Manager $manager, $operationName, $typeName, $resolver, $argsMap = [])
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
     * Overload the getResolver() method to be aware of the polymorphic resolvers
     * @return mixed
     */
    public function getResolver()
    {
    	return $this->createResolverFunction();
    }

}