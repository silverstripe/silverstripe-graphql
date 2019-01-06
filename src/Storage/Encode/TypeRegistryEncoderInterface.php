<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use Exception;

interface TypeRegistryEncoderInterface
{
    /**
     * @param Type $type
     * @return $this
     */
    public function addType(Type $type);

    /**
     * @param Type[] $types
     * @return $this
     */
    public function addTypes($types);

    /**
     * @param Type $type
     * @return $this
     * @throws Exception
     */
    public function removeType(Type $type);

    /**
     * @return void
     * @throws Exception
     */
    public function encode();

    /**
     * @return bool
     */
    public function isEncoded();

    /**
     * @return TypeRegistryInterface
     */
    public function getRegistry();
}