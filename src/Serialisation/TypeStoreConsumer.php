<?php

namespace SilverStripe\GraphQL\Serialisation;

use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;

interface TypeStoreConsumer
{
    /**
     * @param TypeStoreInterface $typeStore
     * @return void
     */
    public function loadFromTypeStore(TypeStoreInterface $typeStore);
}