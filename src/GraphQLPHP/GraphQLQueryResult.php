<?php


namespace SilverStripe\GraphQL\GraphQLPHP;


use GraphQL\Executor\ExecutionResult;
use SilverStripe\GraphQL\Schema\QueryResultInterface;

class GraphQLQueryResult implements QueryResultInterface
{
    /**
     * @var ExecutionResult
     */
    protected $result;
    
    /**
     * GraphQLQueryResult constructor.
     * @param ExecutionResult $result
     */
    public function __construct(ExecutionResult $result)
    {
        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->result->data;
    }

    /**
     * @return Exception[]|null
     */
    public function getErrors()
    {
        return $this->result->errors;
    }
}