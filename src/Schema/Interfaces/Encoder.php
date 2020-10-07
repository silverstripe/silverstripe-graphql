<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * A class that is capable of expressing itself in generated code
 */
interface Encoder
{
    public function encode(): string;
}
