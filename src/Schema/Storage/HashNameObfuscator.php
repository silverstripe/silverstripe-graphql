<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * For the most obscure approach, hash the file names so they're completely undiscoverable.
 */
class HashNameObfuscator implements NameObfuscator
{
    /**
     * @param string $name
     * @return string
     */
    public function obfuscate(string $name): string
    {
        return strtoupper($name[0] ?? '') . md5($name ?? '');
    }
}
