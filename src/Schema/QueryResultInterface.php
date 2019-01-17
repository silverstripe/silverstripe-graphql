<?php


namespace SilverStripe\GraphQL\Schema;

use Exception;

interface QueryResultInterface
{
    /**
     * @return array
     */
    public function getData();

    /**
     * @return Exception[]
     */
    public function getErrors();

}