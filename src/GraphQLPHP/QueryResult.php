<?php


namespace SilverStripe\GraphQL\GraphQLPHP;

use GraphQL\Executor\ExecutionResult;
use SilverStripe\GraphQL\Schema\QueryResultInterface;
use Exception;

class QueryResult implements QueryResultInterface
{
    /**
     * @var ExecutionResult
     */
    protected $result;
    
    /**
     * QueryResult constructor.
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
