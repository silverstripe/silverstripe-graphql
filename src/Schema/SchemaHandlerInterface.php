<?php


namespace SilverStripe\GraphQL\Schema;

use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Schema\Components\AbstractType;

/**
 * Given a schema definition (with resolver logic)
 * and a query with optional params, computes the result for this query.
 */
interface SchemaHandlerInterface
{
    /**
     * @param Schema $schema
     * @param $query
     * @param null $rootValue
     * @param null $context
     * @param null $params
     * @return QueryResultInterface
     */
    public function query(Schema $schema, $query, $rootValue = null, $context = null, $params = null);
}
