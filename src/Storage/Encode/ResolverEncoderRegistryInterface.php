<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;

interface ResolverEncoderRegistryInterface
{
    public function getEncoderForResolver(ResolverAbstraction $resolver);
}