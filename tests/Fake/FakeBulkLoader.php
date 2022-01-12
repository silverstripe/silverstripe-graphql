<?php


namespace Fake;

use SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;

class FakeBulkLoader extends AbstractBulkLoader
{
    public $shouldReturn;

    /**
     * FakeBulkLoader constructor.
     * @param array $shouldReturn
     */
    public function __construct($shouldReturn = [])
    {
        parent::__construct();
        $this->shouldReturn = $shouldReturn;
    }

    public static function getIdentifier(): string
    {
        return 'fake';
    }

    /**
     * @param Collection $collection
     * @return Collection
     * @throws \Exception
     */
    public function collect(Collection $collection): Collection
    {
        return new Collection($this->shouldReturn);
    }
}
