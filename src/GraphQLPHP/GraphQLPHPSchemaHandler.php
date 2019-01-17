<?php


namespace SilverStripe\GraphQL\GraphQLPHP;


use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use SilverStripe\GraphQL\Schema\EmptySchemaException;
use SilverStripe\GraphQL\Schema\SchemaHandlerInterface;
use SilverStripe\GraphQL\Storage\SchemaStorageInterface;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\SchemaAbstraction;

class GraphQLPHPSchemaHandler implements SchemaHandlerInterface
{
    /**
     * @param SchemaAbstraction $schemaAbstract
     * @param $query
     * @param null $rootValue
     * @param null $context
     * @param null $params
     * @return GraphQLQueryResult|\SilverStripe\GraphQL\Schema\QueryResultInterface
     * @throws EmptySchemaException
     */
    public function query(SchemaAbstraction $schemaAbstract, $query, $rootValue = null, $context = null, $params = null)
    {
        $schemaConfig = new SchemaConfig();
        $registry = $schemaAbstract->getTypeRegistry();
        $schemaConfig->setTypeLoader(function ($type) use ($registry) {
            return $registry->getType($type);
        });
        $schemaConfig->setQuery($registry->getType('Query'));
        $schemaConfig->setMutation($registry->getType('Mutation'));

        $schema = new Schema($schemaConfig);

        $result = GraphQL::executeQuery($schema, $query, $rootValue, $context, $params);

        return new GraphQLQueryResult($result);
    }
    
}