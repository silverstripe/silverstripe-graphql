<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * Defines a service that can obfuscate classnames to make their files less discoverable
 */
interface NameObfuscator
{
    /**
     * @param string $name
     * @return string
     */
    public function obfuscate(string $name): string;
}
