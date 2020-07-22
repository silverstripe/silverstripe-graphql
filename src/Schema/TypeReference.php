<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use SilverStripe\Core\Injector\Injectable;

class TypeReference
{
    use Injectable;

    private $typeStr;

    public function __construct(string $typeStr)
    {
        $this->typeStr = $typeStr;
    }

    /**
     * @return Node
     * @throws SyntaxError
     */
    public function toAST(): Node
    {
        return Parser::parseType($this->typeStr, ['noLocation' => true]);
    }

}
