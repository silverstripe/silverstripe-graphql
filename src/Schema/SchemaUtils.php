<?php


namespace SilverStripe\GraphQL\Schema;


class SchemaUtils
{
    /**
     * Pluralises a word if quantity is not one.
     *
     * @param string $singular Singular form of word
     * @return string Pluralised word if quantity is not one, otherwise singular
     */
    public static function pluralise(string $singular): string {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $singular)) {
            $word = substr($singular, 0, -1) . 'ie';
        } else {
            $word = $singular;
        }
        $word .= 's';

        return $word;
    }

}
