<?php


namespace SilverStripe\GraphQL;


interface SchemaPersisterInterface
{

    public function persistSchema($data);

    public function getRegistry();
}
