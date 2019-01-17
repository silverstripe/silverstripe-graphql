<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use Exception;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface TypeRegistryEncoderInterface
{
    /**
     * @param TypeAbstraction $type
     * @return $this
     */
    public function addType(TypeAbstraction $type);

    /**
     * @param TypeAbstraction[] $types
     * @return $this
     */
    public function addTypes($types);

    /**
     * @param TypeAbstraction $type
     * @return $this
     * @throws Exception
     */
    public function removeType(TypeAbstraction $type);

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