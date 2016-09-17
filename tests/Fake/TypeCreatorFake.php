<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\TypeCreator;

class TypeCreatorFake extends TypeCreator {

    public function attributes()
    {
        return [
            'name' => 'TypeCreatorFake',
        ];
    }

}
