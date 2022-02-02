<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * Hashed for less discoverability, but still readable if you focus on it
 */
class HybridObfuscator implements NameObfuscator
{
    /**
     * @param string $name
     * @return string
     */
    public function obfuscate(string $name): string
    {
        return sprintf('%s_%s', $name, md5($name));
    }
}
