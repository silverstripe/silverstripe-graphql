<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Any class that can represent its state as a string. Kind of like serialize,
 * but doesn't need to support unserialization
 */
interface SignatureProvider
{
    public function getSignature(): string;
}
