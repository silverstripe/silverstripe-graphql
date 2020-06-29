<?php


namespace SilverStripe\GraphQL\QueryHandler;


use GraphQL\Type\Schema;

interface QueryHandlerInterface
{
    public function query(Schema $schema, string $query, array $params = []): array;

}
