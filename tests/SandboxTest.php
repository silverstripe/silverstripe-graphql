<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Token;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\AST;
use phpDocumentor\Reflection\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

/**
 * Experimental tests.. delete this.
 */
class SandboxTest extends \PHPUnit_Framework_TestCase
{
    public function testSandbox()
    {
        $fields = [
            'title' => ['prop' => 'title'],
            'bozo' => ['prop' => 'Bozo'],
            'categories' => [
                'prop' => 'Categories',
                'children' => [
                    'title' => ['prop' => 'CategoryTitle'],
                ]
            ],
            'comments' => [
                'prop' => 'Comments',
                'children' => [
                    'Content' => ['prop' => 'Content'],
                    'author' => [
                        'prop' => 'Author',
                        'children' => [
                            'name' => ['prop' => 'Name'],
                            'email' => ['prop' => 'Email']
                        ]
                    ]
                ]
            ]
        ];
        $result = QueryFilter::buildPathsFromFieldMapping($fields);
        $result;
//        $result = Parser::parseType('[MyType]!', ['noLocation' => true]);
//        $node = $result;
//        $path = [];
//        while($node->kind !== NodeKind::NAMED_TYPE) {
//            $path[] = $node->kind;
//            $node = $node->type;
//        }
//        $named = $node->name->value;
//        $code = '';
//        foreach ($path as $token) {
//            $code .= 'Type::' . $token . '(';
//        }
//        $code .= 'Type::' . $named . '()';
//        $code .= str_repeat(')', count($path));
//        $type = AST::typeFromAST(new Schema(SchemaConfig::create()), $result);
//        $type;
//          $parser = new Parser(new Source('MyField(MyArg: String!)', ['noLocation' => true]));
//          $parser->skip(Token::SOF);
//          $name = $parser->parseName();
//          $args = $parser->parseArgumentDefs(true);
//          $args;
    }
}
