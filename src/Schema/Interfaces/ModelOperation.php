<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface ModelOperation
{
    public function getModel(): SchemaModelInterface;
}
