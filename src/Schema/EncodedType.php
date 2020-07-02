<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use SilverStripe\View\ViewableData;

class EncodedType extends ViewableData implements Encoder
{
    const TYPE_CLASS_NAME = 'Types';

    /**
     * @var Node
     */
    private $ast;

    private static $typeMap = [
        NodeKind::LIST_TYPE => 'listOf',
        NodeKind::NON_NULL_TYPE => 'nonNull',
    ];

    /**
     * EncodedType constructor.
     * @param Node $ast
     */
    public function __construct(Node $ast)
    {
        parent::__construct();
        $this->ast = $ast;
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function forTemplate()
    {
        return $this->encode();
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function encode(): string
    {
        $node = $this->ast;
        $path = [];
        while($node && !$node instanceof NamedTypeNode) {
            $path[] = $node->kind;
            $node = $node->type;
        }
        SchemaBuilder::invariant($node, 'No named type was found on %s', $this->typeStr);
        $named = $node->name->value;

        $code = '';
        foreach ($path as $token) {
            $func = static::$typeMap[$token] ?? null;
            SchemaBuilder::invariant($func, 'Node kind %s is invalid on %s', $token, $this->typeStr);
            $code .= self::TYPE_CLASS_NAME . '::' . $func . '(';
        }
        $code .= self::TYPE_CLASS_NAME . '::' . $named . '()';
        $code .= str_repeat(')', count($path));

        return $code;
    }
}
