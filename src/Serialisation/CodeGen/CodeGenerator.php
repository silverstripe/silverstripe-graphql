<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

interface CodeGenerator
{
    /**
     * @return string
     */
    public function toCode();
}