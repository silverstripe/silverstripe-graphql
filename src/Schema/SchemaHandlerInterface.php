<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\SchemaAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface SchemaHandlerInterface
{
    /**
     * @param SchemaAbstraction $schema
     * @param $query
     * @param null $rootValue
     * @param null $context
     * @param null $params
     * @return QueryResultInterface
     */
    public function query(SchemaAbstraction $schema, $query, $rootValue = null, $context = null, $params = null);

}