<?php


namespace SilverStripe\GraphQL\Schema\Type;


use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\View\ViewableData;

/**
 * A type that can be expressed as generated PHP code
 */
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
     * @return bool
     */
    public function isList(): bool
    {
        return $this->hasWrapper(NodeKind::LIST_TYPE);
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->hasWrapper(NodeKind::NON_NULL_TYPE);
    }

    /**
     * @param string $nodeKind
     * @return bool
     */
    private function hasWrapper(string $nodeKind): bool
    {
        list ($named, $path) = $this->getTypeName();

        if (empty($path)) {
            return false;
        }

        return $path[0] === $nodeKind;
    }

    /**
     * @return array
     */
    public function getTypeName(): array
    {
        $node = $this->ast;
        $path = [];
        while($node && !$node instanceof NamedTypeNode) {
            $path[] = $node->kind;
            $node = $node->type;
        }

        $named = $node ? $node->name->value : null;

        return [$named, $path];
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function encode(): string
    {
        list ($named, $path) = $this->getTypeName();
        Schema::invariant($named, 'No named type was found on %s', $this->ast);

        $code = '';
        foreach ($path as $token) {
            $func = static::$typeMap[$token] ?? null;
            Schema::invariant($func, 'Node kind %s is invalid on %s', $token, $this->ast);
            $code .= self::TYPE_CLASS_NAME . '::' . $func . '(';
        }
        $code .= self::TYPE_CLASS_NAME . '::' . $named . '()';
        $code .= str_repeat(')', count($path));

        return $code;
    }
}
