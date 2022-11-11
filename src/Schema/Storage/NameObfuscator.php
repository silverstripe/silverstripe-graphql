<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * Defines a service that can obfuscate classnames to make their files less discoverable in IDE Search
 */
interface NameObfuscator
{
    public function obfuscate(string $name): string;
}
