<?php


namespace SilverStripe\GraphQL\Schema\Storage;

/**
 * For the most obscure approach, hash the file names so they're highly undiscoverable in IDE search
 */
class HashNameObfuscator implements NameObfuscator
{
    public function obfuscate(string $name): string
    {
        return strtoupper($name[0] ?? '') . md5($name ?? '');
    }
}
