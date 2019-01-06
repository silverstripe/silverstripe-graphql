<?php

namespace SilverStripe\GraphQL\Storage\Encode;

class UnionTypeFactory extends ResolverFactory
{
    /**
     * UnionTypeFactory constructor.
     * @param array $context
     */
    public function __construct(array $context = [])
    {
        if (!isset($context['types'])) {
            $context['types'] = [];
        }

        parent::__construct($context);
    }

    /**
     * @param TypeRegistryInterface $registry
     * @return callable|\Closure
     */
    public function createResolver(TypeRegistryInterface $registry)
    {
        $types = $this->context['types'];
        return function () use ($registry, $types) {
            return array_filter(
                array_map(function ($item) use ($registry) {
                    return $registry->has($item)? $registry->get($item) : null;
                }, $types)
            );
        };
    }
}