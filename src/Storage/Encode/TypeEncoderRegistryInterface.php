<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface TypeEncoderRegistryInterface
{
    /**
     * @param TypeAbstraction $type
     * @return TypeEncoderInterface
     */
    public function getEncoderForType(TypeAbstraction $type);
}