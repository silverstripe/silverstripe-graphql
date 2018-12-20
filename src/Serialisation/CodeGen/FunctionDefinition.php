<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

class FunctionDefinition implements CodeString
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $tabLevel;

    /**
     * FunctionDefinition constructor.
     * @param $php
     * @param int $tabLevel
     */
    public function __construct($php, $tabLevel = 1)
    {
        $this->code = $php;
        $this->tabLevel = $tabLevel;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $tabs = str_repeat("\t", $this->tabLevel);
        $code = $this->code;
        return <<<PHP
function () {
{$tabs}return $code;
{$tabs}}
PHP;

    }
}