<?php


namespace SilverStripe\GraphQL\Schema\Type;

use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Uniform way of referring to a type as a string. Accepts default value syntax
 */
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

        return in_array($nodeKind, $path);
    }

    /**
     * @return array
     */
    public function getTypeName(): array
    {
        $node = $this->toAST();
        $path = [];
        while ($node && !$node instanceof NamedTypeNode) {
            $path[] = $node->kind;
            $node = $node->type;
        }

        $named = $node ? $node->name->value : null;

        return [$named, $path];
    }

    /**
     * @return string
     */
    public function getNamedType(): string
    {
        return $this->getTypeName()[0];
    }

    /**
     * @return string
     */
    public function getRawType(): string
    {
        return $this->typeStr;
    }

    /**
     * @return bool
     */
    public function isInternal(): bool
    {
        $type = $this->getNamedType();
        return Schema::isInternalType($type);
    }

    public static function createFromPath(string $name, array $path = []): TypeReference
    {
        $str = '';
        foreach ($path as $item) {
            if ($item === NodeKind::LIST_TYPE) {
                $str .= '[';
            }
        }
        $str .= $name;
        foreach (array_reverse($path) as $item) {
            if ($item === NodeKind::LIST_TYPE) {
                $str .= ']';
            } elseif ($item === NodeKind::NON_NULL_TYPE) {
                $str .= '!';
            }
        }

        return TypeReference::create($str);
    }
}
