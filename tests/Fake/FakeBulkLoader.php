<?php


namespace Fake;

use SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;

class FakeBulkLoader extends AbstractBulkLoader
{
    public $shouldReturn;

    /**
     * FakeBulkLoader constructor.
     * @param array|null $shouldReturn
     */
    public function __construct(?array $shouldReturn = null)
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
        if ($this->shouldReturn) {
            return new Collection($this->shouldReturn);
        }

        return $collection;
    }
}
