<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

class Helpers
{
    /**
     * Normalizes a value: Converts nulls, booleans, integers,
     * floats, strings and arrays into their respective nodes
     *
     * Copied from BuilderHelpers class marked as internal in PhpParser package.
     * Enhanced with recognition of ExpressionProvider instances
     *
     * @param Expr|bool|null|int|float|string|array $value The value to normalize
     *
     * @return Expr The normalized value
     */
    public static function normaliseValue($value)
    {
        if ($value instanceof Expr) {
            return $value;
        } else if ($value instanceof ExpressionProvider) {
            return $value->getExpression();
        } else if (is_null($value)) {
            return new Expr\ConstFetch(
                new Name('null')
            );
        } else if (is_bool($value)) {
            return new Expr\ConstFetch(
                new Name($value ? 'true' : 'false')
            );
        } elseif (is_int($value)) {
            return new Scalar\LNumber($value);
        } elseif (is_float($value)) {
            return new Scalar\DNumber($value);
        } elseif (is_string($value)) {
            return new Scalar\String_($value);
        } elseif (is_array($value)) {
            $items = [];
            $lastKey = -1;
            foreach ($value as $itemKey => $itemValue) {
                // for consecutive, numeric keys don't generate keys
                if (null !== $lastKey && ++$lastKey === $itemKey) {
                    $items[] = new Expr\ArrayItem(
                        self::normaliseValue($itemValue)
                    );
                } else {
                    $lastKey = null;
                    $items[] = new Expr\ArrayItem(
                        self::normaliseValue($itemValue),
                        self::normaliseValue($itemKey)
                    );
                }
            }

            return new Expr\Array_($items);
        }else {
            throw new \LogicException('Invalid value');
        }
    }

    /**
     * @param scalar $key
     * @param scalar $val
     * @return ArrayItem
     */
    public static function buildArrayItem($key, $val)
    {
        return new ArrayItem(
            static::normaliseValue($key),
            static::normaliseValue($val)
        );
    }

    /**
     * @param array $data
     * @param array $omittedKeys
     * @param bool $removeNull
     * @return ArrayItem[]
     */
    public static function buildArrayItems(array $data, $omittedKeys = [], $removeNull = true)
    {
        $validKeys = array_diff(array_keys($data), $omittedKeys);
        if ($removeNull) {
            $validKeys = array_filter($validKeys, function ($key) use ($data) {
                return $data[$key] !== null;
            });
        }
        $items = array_map(function ($key) use ($data) {
            return static::buildArrayItem($data[$key], $key);
        }, $validKeys);

        return $items;
    }

    /**
     * @param array $data
     * @param array $omittedKeys
     * @param bool $removeNull
     * @return Array_
     */
    public static function buildArrayExpression(array $data, $omittedKeys = [], $removeNull = true)
    {
        return new Array_(static::buildArrayItems($data, $omittedKeys, $removeNull));
    }

}