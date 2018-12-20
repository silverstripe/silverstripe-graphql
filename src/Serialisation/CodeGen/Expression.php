<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

class Expression implements CodeString
{
    /**
     * @var string
     */
    protected $code;

    /**
     * Expression constructor.
     * @param $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }
}