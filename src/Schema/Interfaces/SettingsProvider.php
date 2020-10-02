<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface SettingsProvider
{
    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function getSetting(string $key, $default = null);
}
