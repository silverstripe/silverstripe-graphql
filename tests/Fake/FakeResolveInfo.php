<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use SilverStripe\Dev\TestOnly;

class FakeResolveInfo extends ResolveInfo implements TestOnly
{
    public function __construct(array $options = [])
    {
        // webonyx/graphql-php v0.12
        if (!property_exists(__CLASS__, 'fieldDefinition')) {
            return;
        }
        // webonyx/graphql-php v14
        // This is a minimal implementation that's just good enough
        // to get unit tests to pass
        $name = 'fake';
        $type = Type::string();
        foreach ($options as $key => $value) {
            if ($key === 'name') {
                $name = $value;
            }
            if ($key === 'type') {
                if ($value === 'int') {
                    $type = Type::int();
                }
            }
        }
        parent::__construct(
            FieldDefinition::create(['name' => $name, 'type' => $type]),
            [],
            new ObjectType(['name' => 'abc', 'fields' => [$name => $type]]),
            [],
            new Schema([]),
            [],
            '',
            null,
            []
        );
    }
}
