<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use InvalidArgumentException;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use Closure;

class TypeSerialiser implements TypeSerialiserInterface
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
     * TypeSerialiser constructor.
     */
    public function __construct()
    {
        $this->builtInTypes = Type::getAllBuiltInTypes();
    }

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

    public function tokeniseType(Type $type)
    {
        return $this->unserialiseType($type->toString());
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

            if (isset($this->builtInTypes[$namedTypeStr])) {
                $namedType = $this->builtInTypes[$namedTypeStr];
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
                return trim($str, self::LIST_OF);
            case self::NON_NULL:
                return rtrim($str, self::NON_NULL);
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

    /**
     * @param string $symbol
     * @param string $wrapppedTypeCode
     * @return string
     */
    protected function getTypeCodeForSymbol($symbol, $wrapppedTypeCode)
    {
        switch ($symbol) {
            case self::NON_NULL:
                return sprintf('Type::nonNull(%s)', $wrapppedTypeCode);

            case self::LIST_OF:
                return sprintf('Type::listOf(%s)', $wrapppedTypeCode);
            default:
                throw new InvalidArgumentException(sprintf(
                    'Invalid symbol "%s"',
                    $symbol
                ));
        }
    }

    public function exportType(Type $type)
    {
        $wrap = $this->tokeniseType($type);
        $namedType = null;
        $namedTypeStr = array_pop($wrap);

        if (isset($this->builtInTypes[$namedTypeStr])) {
            $namedType = sprintf('Type::%s()', strtolower($namedTypeStr));
        } else {
            // Todo: coupling to TypeStore. Maybe pass this in as a param and have TypeStore export code
            $namedType = sprintf('$this->get(\'%s\')', $namedTypeStr);
        }
        $type = $namedType;
        foreach (array_reverse($wrap) as $symbol) {
            $type = $this->getTypeCodeForSymbol($symbol, $type);
        }

        return $type;
    }


}