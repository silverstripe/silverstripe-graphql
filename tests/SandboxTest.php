<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Token;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\AST;
use SilverStripe\Dev\SapphireTest;

/**
 * Experimental tests.. delete this.
 */
class SandboxTest extends SapphireTest
{
    public function testSandbox()
    {
        $parser = new Parser(new Source('[MyType]!', ['noLocation' => true]));
        $parser->skip(Token::SOF);
        $result = $parser->parseTypeReference();

        $node = $result;
        $path = [];
        while($node->kind !== NodeKind::NAMED_TYPE) {
            $path[] = $node->kind;
            $node = $node->type;
        }
        $named = $node->name->value;
        $code = '';
        foreach ($path as $token) {
            $code .= 'Type::' . $token . '(';
        }
        $code .= 'Type::' . $named . '()';
        $code .= str_repeat(')', count($path));
        $type = AST::typeFromAST(new Schema(SchemaConfig::create()), $result);
        $type;
          $parser = new Parser(new Source('MyField(MyArg: String!)', ['noLocation' => true]));
          $parser->skip(Token::SOF);
          $name = $parser->parseName();
          $args = $parser->parseArgumentDefs(true);
          $args;
    }
}
