<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface TypeEncoderRegistryInterface
{
    public function getEncoderForType(TypeAbstraction $type);
}