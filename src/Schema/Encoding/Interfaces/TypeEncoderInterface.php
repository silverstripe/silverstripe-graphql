<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider;

interface TypeEncoderInterface extends TypeExpressionProvider
{
    /**
     * @param AbstractType $type
     * @return bool
     */
    public function appliesTo(AbstractType $type);
}
