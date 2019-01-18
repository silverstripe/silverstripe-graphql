<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Factories;

use SilverStripe\GraphQL\Schema\Encoding\Factories;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

class UnionTypeFactory extends Factories\RegistryAwareClosureFactory
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
    public function createClosure(TypeRegistryInterface $registry)
    {
        $types = $this->context['types'];
        return function () use ($registry, $types) {
            return array_filter(
                array_map(function ($item) use ($registry) {
                    return $registry->hasType($item)? $registry->getType($item) : null;
                }, $types)
            );
        };
    }
}