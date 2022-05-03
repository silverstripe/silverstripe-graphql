<?php


namespace SilverStripe\GraphQL\Schema\Type;

use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\View\ViewableData;

/**
 * A type that can be expressed as generated PHP code
 */
class EncodedType implements Encoder
{
    use Injectable;
    use Configurable;

    private TypeReference $ref;

    private static array $typeMap = [
        NodeKind::LIST_TYPE => 'listOf',
        NodeKind::NON_NULL_TYPE => 'nonNull',
    ];

    public function __construct(TypeReference $ref)
    {
        $this->ref = $ref;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function encode(): string
    {
        list ($named, $path) = $this->ref->getTypeName();
        Schema::invariant($named, 'No named type was found on %s', $this->ref->getRawType());

        $code = '';
        foreach ($path as $token) {
            $func = static::$typeMap[$token] ?? null;
            Schema::invariant($func, 'Node kind %s is invalid on %s', $token, $this->ref->getRawType());
            $code .= CodeGenerationStore::TYPE_CLASS_NAME . '::' . $func . '(';
        }
        $code .= CodeGenerationStore::TYPE_CLASS_NAME . '::' . $named . '()';
        $code .= str_repeat(')', count($path ?? []));

        return $code;
    }
}
