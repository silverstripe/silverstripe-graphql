<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;

class DataObjectModelTest extends SapphireTest
{
    /**
     * @dataProvider provideGetTypeDescription
     */
    public function testGetTypeDescription(?string $description, ?string $expected): void
    {
        DataObjectFake::config()->set('class_description', $description);
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $this->assertEquals($expected, $model->getTypeDescription());
    }

    public static function provideGetTypeDescription(): array
    {
        return [
            'No description' => [null, null],
            'With description' => ['A description', 'A description'],
        ];
    }
}
