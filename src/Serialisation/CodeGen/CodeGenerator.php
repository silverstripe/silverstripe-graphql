<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

use SilverStripe\View\ViewableData;

interface CodeGenerator
{
    /**
     * @param string|null $varName
     * @return ViewableData
     */
    public function toCode();
}