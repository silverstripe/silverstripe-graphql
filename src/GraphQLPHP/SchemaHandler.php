<?php


namespace SilverStripe\GraphQL\GraphQLPHP;


use GraphQL\GraphQL;
use GraphQL\Type\SchemaConfig;
use GraphQL\Type\Schema as GraphQLPHPSchema;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Schema\QueryResultInterface;
use SilverStripe\GraphQL\Schema\SchemaHandlerInterface;

class SchemaHandler implements SchemaHandlerInterface
{
    /**
     * @param Schema $schemaAbstract
     * @param $query
     * @param null $rootValue
     * @param null $context
     * @param null $params
     * @return QueryResultInterface
     */
    public function query(Schema $schemaAbstract, $query, $rootValue = null, $context = null, $params = null)
    {
        $schemaConfig = new SchemaConfig();
        $registry = $schemaAbstract->getTypeRegistry();
        $schemaConfig->setTypeLoader(function ($type) use ($registry) {
            return $registry->getType($type);
        });
        $schemaConfig->setQuery($registry->getType('Query'));
        $schemaConfig->setMutation($registry->getType('Mutation'));

        $schema = new GraphQLPHPSchema($schemaConfig);

        $result = GraphQL::executeQuery($schema, $query, $rootValue, $context, $params);

        return new QueryResult($result);
    }
    
}