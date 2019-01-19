<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface;

interface FunctionEncoderRegistryInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return FunctionEncoderInterface
     */
    public function getEncoderForResolver(AbstractFunction $resolver);
}
