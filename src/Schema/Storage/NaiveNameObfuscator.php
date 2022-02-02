<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * Naive implementation for debugging. Allow the class file name to be the same as the class
 */
class NaiveNameObfuscator implements NameObfuscator
{
    /**
     * @param string $name
     * @return string
     */
    public function obfuscate(string $name): string
    {
        return $name;
    }
}
