<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderInterface;

interface ResolverEncoderRegistryInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return ResolverEncoderInterface
     */
    public function getEncoderForResolver(AbstractFunction $resolver);
}
