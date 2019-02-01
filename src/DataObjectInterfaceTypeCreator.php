<?php

namespace SilverStripe\GraphQL;

use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\InternalType;
use SilverStripe\GraphQL\Schema\Components\TypeReference;

/**
 * Base interface for any {@link DataObject} passed back as a node.
 */
class DataObjectInterfaceTypeCreator extends InterfaceTypeCreator
{

    public function attributes()
    {
        /** @skipUpgrade */
        return [
            'name' => 'DataObject',
            'description' => 'Base Interface',
        ];
    }

    public function fields()
    {
        return [
            Field::create(
                'id',
                TypeReference::create(InternalType::id())->setRequired(true)
            ),
            Field::create(
                'created',
                TypeReference::create(InternalType::string())
            ),
            Field::create(
                'lastEdited',
                TypeReference::create(InternalType::string())
            ),
        ];
    }

    public function resolveType($object)
    {
        $type = null;

        if ($fqnType = $this->manager->getType(get_class($object))) {
            $type = $fqnType;
        }

        if ($baseType = $this->manager->getType(get_class($object))) {
            $type = $baseType;
        }

        return $type;
    }
}
