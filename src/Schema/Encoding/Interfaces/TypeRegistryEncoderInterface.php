<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use Exception;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

interface TypeRegistryEncoderInterface
{
    /**
     * @param AbstractType $type
     * @return $this
     */
    public function addType(AbstractType $type);

    /**
     * @param AbstractType[] $types
     * @return $this
     */
    public function addTypes($types);

    /**
     * @param AbstractType $type
     * @return $this
     * @throws Exception
     */
    public function removeType(AbstractType $type);

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
