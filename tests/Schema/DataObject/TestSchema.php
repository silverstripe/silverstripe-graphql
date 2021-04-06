<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\ModelCreator;
use SilverStripe\GraphQL\Schema\DataObject\ReadCreator;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;

class TestSchema extends Schema implements TestOnly
{
    public function __construct($key = 'test')
    {
        parent::__construct($key, new SchemaConfig([
            'modelCreators' => [ ModelCreator::class ],
            'modelConfig' => [
                'DataObject' => [
                    'base_fields' => ['ID' => 'ID'],
                    'operations' => [
                        'read' => [
                            'class' => ReadCreator::class,
                        ],
                    ],
                ],
            ]
        ]));
    }
}
