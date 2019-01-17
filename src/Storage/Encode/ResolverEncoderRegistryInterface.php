<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;

interface ResolverEncoderRegistryInterface
{
    /**
     * @param ResolverAbstraction $resolver
     * @return ResolverEncoderInterface
     */
    public function getEncoderForResolver(ResolverAbstraction $resolver);
}