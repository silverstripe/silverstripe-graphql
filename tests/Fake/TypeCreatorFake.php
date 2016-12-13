<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\TypeCreator;

class TypeCreatorFake extends TypeCreator
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
            'MyField'
        ];
    }
}
