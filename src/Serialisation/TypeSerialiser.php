<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use Closure;

class TypeSerialiser implements TypeSerialiserInterface
{
    const LIST_OF = 'ListOf';

    const NON_NULL = 'NonNull';

    const RXP_LIST_OF = '/^\[[A-Za-z0-9_!]+\]$/';

    const RXP_NON_NULL = '/\!$/';

    /**
     * @var array
     */
    protected $wrap = [];

    /**
     * @param Type $type
     * @return string
     */
    public function serialiseType(Type $type)
    {
        return $type->toString();
    }

    /**
     * @param $typeStr
     * @return array
     */
    public function unserialiseType($typeStr)
    {
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
     * @param string $typeStr
     * @return Closure
     */
    public function getTypeCreator($typeStr)
    {
        $wrap = $this->unserialiseType($typeStr);

        return function (TypeStoreInterface $typeStore) use ($wrap) {
            $namedType = null;
            $namedTypeStr = array_pop($wrap);
            $builtIns = Type::getAllBuiltInTypes();
            if (isset($builtIns[$namedTypeStr])) {
                $namedType = $builtIns[$namedTypeStr];
            } else {
                $namedType = $typeStore->getType($namedTypeStr);
            }
            $type = $namedType;
            foreach (array_reverse($wrap) as $symbol) {
                $typeCreator = $this->getTypeCreatorForSymbol($symbol);
                $type = $typeCreator($type);
            }

            return $type;
        };

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
                return preg_replace(self::RXP_LIST_OF, '', $str);
            case self::NON_NULL:
                return preg_replace(self::RXP_NON_NULL, '', $str);
            default:
                throw new InvalidArgumentException(sprintf('Invalid symbol: "%s"', $symbol));
        }
    }

    /**
     * @param $symbol
     * @return Closure
     */
    protected function getTypeCreatorForSymbol($symbol)
    {
        switch ($symbol) {
            case self::NON_NULL:
                return function ($type) {
                    return Type::nonNull($type);
                };

            case self::LIST_OF:
                return function ($type) {
                    return Type::listOf($type);
                };
            default:
                throw new InvalidArgumentException(sprintf(
                    'Invalid symbol "%s"',
                    $symbol
                ));
        }
    }

}