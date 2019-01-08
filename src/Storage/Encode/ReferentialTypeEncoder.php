<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;

class ReferentialTypeEncoder implements TypeExpressionProvider
{
    const LIST_OF = '[]';

    const NON_NULL = '!';

    const RXP_LIST_OF = '/^\[[A-Za-z0-9_!]+\]$/';

    const RXP_NON_NULL = '/\!$/';

    /**
     * @var array
     */
    protected $wrap = [];

    /**
     * @var array|Type[]
     */
    protected $builtInTypes = [];

    /**
     * @var BuilderFactory
     */
    protected $factory;

    /**
     * @var NamedTypeFetcherInterface
     */
    protected $customTypeFetcher;

    /**
     * TypeSerialiser constructor.
     * @param BuilderFactory $factory
     * @param NamedTypeFetcherInterface $customTypeFetcher
     */
    public function __construct(BuilderFactory $factory, NamedTypeFetcherInterface $customTypeFetcher)
    {
        $this->builtInTypes = Type::getAllBuiltInTypes();
        $this->factory = $factory;
        $this->customTypeFetcher = $customTypeFetcher;
    }

    /**
     * @param Type $type
     * @return array
     */
    public function tokeniseType(Type $type)
    {
        $typeStr = $type->toString();
        $wrap = [];
        while($symbol = $this->getSymbolForOuterType($typeStr)) {
            $wrap[] = $symbol;
            if ($symbol === $typeStr) {
                break;
            }
            $typeStr = $this->unwrapTypeString($typeStr, $symbol);
        }

        return $wrap;

    }

    /**
     * @param string $str
     * @return string
     */
    protected function getSymbolForOuterType($str)
    {
        if (preg_match(self::RXP_LIST_OF, $str)) {
            return self::LIST_OF;
        } else if (preg_match(self::RXP_NON_NULL, $str)) {
            return self::NON_NULL;
        }

        return $str;
    }

    /**
     * @param $str
     * @param $symbol
     * @return string
     */
    protected function unwrapTypeString($str, $symbol)
    {
        switch ($symbol) {
            case self::LIST_OF:
                return trim($str, self::LIST_OF);
            case self::NON_NULL:
                return rtrim($str, self::NON_NULL);
            default:
                throw new InvalidArgumentException(sprintf('Invalid symbol: "%s"', $symbol));
        }
    }

    /**
     * @param string $symbol
     * @param Expr $wrappedTypeExpr
     * @return Expr
     */
    protected function getTypeExpressionForSymbol($symbol, Expr $wrappedTypeExpr)
    {
        switch ($symbol) {
            case self::NON_NULL:
                return $this->factory->staticCall(
                    Type::class,
                    'nonNull',
                    [
                        $wrappedTypeExpr,
                    ]
                );

            case self::LIST_OF:
                return $this->factory->staticCall(
                    Type::class,
                    'listOf',
                    [
                        $wrappedTypeExpr,
                    ]
                );

            default:
                throw new InvalidArgumentException(sprintf(
                    'Invalid symbol "%s"',
                    $symbol
                ));
        }
    }

    /**
     * @param Type $type
     * @return Expr
     */
    public function getExpression(Type $type)
    {
        $wrap = $this->tokeniseType($type);
        $namedType = null;
        $namedTypeStr = array_pop($wrap);

        if (isset($this->builtInTypes[$namedTypeStr])) {
            $namedType = $this->factory->staticCall(Type::class, strtolower($namedTypeStr));
        } else {
            $namedType = $this->customTypeFetcher->getExpression($namedTypeStr);
        }
        $type = $namedType;
        foreach (array_reverse($wrap) as $symbol) {
            $type = $this->getTypeExpressionForSymbol($symbol, $type);
        }

        return $type;
    }
}