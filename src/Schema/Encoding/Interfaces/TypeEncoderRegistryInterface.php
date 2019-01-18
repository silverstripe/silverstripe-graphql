<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderInterface;

interface TypeEncoderRegistryInterface
{
    /**
     * @param AbstractType $type
     * @return TypeEncoderInterface
     */
    public function getEncoderForType(AbstractType $type);
}
