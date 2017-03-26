<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\TypeCreator;

class TypeCreatorFake extends TypeCreator implements TestOnly
{
    public function attributes()
    {
        return [
            'name' => 'TypeCreatorFake',
        ];
    }

    public function fields()
    {
        return [
            'MyField' => [
                'type' => Type::string()
            ]
        ];
    }
}
