<?php


namespace SilverStripe\GraphQL\Tests\GraphQLPHP;


use GraphQL\Executor\ExecutionResult;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\GraphQLPHP\QueryResult;

class QueryResultTest extends SapphireTest
{
    public function testQueryResult()
    {
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $errors = ['something failed'];
        $executionResult = new ExecutionResult($data, $errors);
        $result = new QueryResult($executionResult);

        $this->assertCount(2, $result->getData());
        $this->assertCount(1, $result->getErrors());
        $this->assertEquals($data, $result->getData());
        $this->assertEquals($errors, $result->getErrors());
    }
}