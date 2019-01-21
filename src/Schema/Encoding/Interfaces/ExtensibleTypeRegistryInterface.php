<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

interface ExtensibleTypeRegistryInterface extends TypeRegistryInterface
{

    /**
     * @param TypeRegistryInterface $registry
     * @return $this
     */
    public function addExtension(TypeRegistryInterface $registry);
}
