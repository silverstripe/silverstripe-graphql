<?php


namespace SilverStripe\GraphQL\Tests\Fake;


use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;

class ClosureFactoryFake implements ClosureFactoryInterface
{
    public $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function createClosure()
    {
        return function () {
            $key = array_keys($this->params)[0];
            return $this->params[$key];
        };
    }

    public function getContext()
    {
        return $this->params;
    }
}