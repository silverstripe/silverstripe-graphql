<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Factory;
use Exception;

class CollectionFactory implements Factory
{
    /**
     * @param string $service
     * @param array $params
     * @return Collection
     * @throws Exception
     */
    public function create($service, array $params = []): Collection
    {
        $loader = require(BASE_PATH . '/vendor/autoload.php');
        return new Collection($loader, ...$params);
    }
}
