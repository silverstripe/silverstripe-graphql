<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Type\Definition\Type;
use Closure;

/*
 * TODO: Refactor to be a TypeTokeniser or something to that effect
 */
interface TypeSerialiserInterface
{
    /**
     * @param Type $type
     * @return string
     */
    public function serialiseType(Type $type);

    /**
     * @param string $typeStr
     * @return array
     */
    public function unserialiseType($typeStr);

    /**
     * @param string $typeStr
     * @return Closure
     */
    public function getTypeCreator($typeStr);

    /**
     * @param Type $type
     * @return string
     */
    public function exportType(Type $type);
}