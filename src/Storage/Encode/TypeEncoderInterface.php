<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;

interface TypeEncoderInterface extends TypeExpressionProvider
{
    /**
     * @param Type $type
     * @return string
     */
    public function getName(Type $type);

    /**
     * @param Type $type
     * @return bool
     */
    public function appliesTo(Type $type);

    /**
     * @param Type $type
     * @throws Error
     */
    public function assertValid(Type $type);

}