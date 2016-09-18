<?php

namespace Chillu\GraphQL\Tests\Fake;

use Chillu\GraphQL\TypeCreator;

class TypeCreatorFake extends TypeCreator
{
    public function attributes()
    {
        return [
            'name' => 'TypeCreatorFake',
        ];
    }
}
