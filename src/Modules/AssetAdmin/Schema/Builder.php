<?php


namespace SilverStripe\GraphQL\Modules\AssetAdmin\Schema;

use SilverStripe\Assets\File;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\Core\ArrayLib;

if (!interface_exists(SchemaUpdater::class)) {
    return;
}

class Builder implements SchemaUpdater
{
    public static function updateSchema(Schema $schema): void
    {
        $categoryValues = array_map(function ($category) {
            return ['value' => $category];
        }, File::config()->get('app_categories') ?? []);

        // Sanitise GraphQL Enum aliases (some contain slashes)
        foreach ($categoryValues as $key => $v) {
            unset($categoryValues[$key]);
            $newKey = strtoupper(preg_replace('/[^[[:alnum:]]]*/', '', $key ?? '') ?? '');
            $categoryValues[$newKey] = $v;
        }

        $schema->addEnum(Enum::create('AppCategory', $categoryValues));
    }
}
