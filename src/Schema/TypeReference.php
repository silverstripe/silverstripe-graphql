<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Token;
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
        $parser = new Parser(new Source($this->typeStr, ['noLocation' => true]));
        $parser->skip(Token::SOF);

        return $parser->parseTypeReference();
    }
}
