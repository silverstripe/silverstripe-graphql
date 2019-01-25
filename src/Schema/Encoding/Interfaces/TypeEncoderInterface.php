<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider;

/**
 * Converts a given abstract type, e.g. into PHP code.
 * These are stored in a {@link TypeEncoderRegistryInterface}.
 * The first one to return true on appliesTo(AbstractType) gets the job.
 */
interface TypeEncoderInterface extends TypeExpressionProvider
{
    /**
     * @param AbstractType $type
     * @return bool
     */
    public function appliesTo(AbstractType $type);
}
