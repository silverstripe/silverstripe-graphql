<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

interface TypeParserInterface
{
    public function getArgTypeName();

    public function getType();
}
