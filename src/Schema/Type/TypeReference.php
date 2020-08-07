<?php


namespace SilverStripe\GraphQL\Schema\Type;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use SilverStripe\Core\Injector\Injectable;

class TypeReference
{
    use Injectable;

    private $typeStr;

    /**
     * @var
     */
    private $defaultValue;

    public function __construct(string $typeStr)
    {
        // The Type = 'default value' syntax isn't parsed by the graphql-php library, so
        // we just handle this internally.
        if (stristr($typeStr, '=') !== false) {
            list ($type, $defaultValue) = explode('=', $typeStr);
            $this->defaultValue = trim($defaultValue);
            $this->typeStr = trim($type);
        } else {
            $this->typeStr = $typeStr;
        }
    }

    /**
     * @return Node
     */
    public function toAST(): Node
    {
        return Parser::parseType($this->typeStr, ['noLocation' => true]);
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

}
