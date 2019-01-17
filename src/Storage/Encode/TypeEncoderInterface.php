<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface TypeEncoderInterface extends TypeExpressionProvider
{
    /**
     * @param Type $type
     * @return bool
     */
    public function appliesTo(TypeAbstraction $type);


}