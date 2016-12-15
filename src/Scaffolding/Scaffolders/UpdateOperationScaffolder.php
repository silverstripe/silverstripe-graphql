<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\GraphQL\Scaffolding\Resolvers\Update;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\ORM\DataList;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\Type;

/**
 * Scaffolds a generic update operation for DataObjects
 */
class UpdateOperationScaffolder extends MutationScaffolder
{

    use DataObjectTypeTrait;

    /**
     * UpdateOperationScaffolder constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'update' . ucfirst($this->typeName()),
            $this->typeName()
        );

        $args = [];
        $instance = $this->getDataObjectInstance();

        // Setup default input args.. Placeholder!
        foreach ($instance->db() as $dbFieldName => $dbFieldType) {
            $result = $instance->obj($dbFieldName);
            $typeName = $result->config()->graphql_type;
            $args[$dbFieldName] = $typeName;
        }

        $this->args = $args;

        // Todo: this is totally half baked
        $this->setResolver(function ($object, array $args, $context, $info) {
            if (singleton($this->dataObjectName)->canEdit()) {
                $obj = DataList::create($this->dataObjectName)
                    ->byID($args['ID']);

                if ($obj) {
                    $obj->update($args['Input']);
                    $obj->write();
                }

                return $obj;
            } else {
                // permission error
            }
        });

    }


    /**
     * Use a generated Input type, and require an ID
     * @return array
     */
    protected function createArgs()
    {
        return [
            'ID' => (new TypeParser('ID!'))->toArray(),
            'Input' => [
                'type' => Type::nonNull($this->generateInputType())
            ]
        ];
    }

    /**
     * Based on the args provided, create an Input type to add to the Manager
     * @return InputObjectType
     */
    protected function generateInputType()
    {
        $fields = [];
        $args = $this->args;
        unset($args['ID']);

        foreach ($args as $fieldName => $typeStr) {
            $arr = (new TypeParser($typeStr))->toArray();
            $arr['name'] = $fieldName;
            $fields[] = $arr;
        }
        return new InputObjectType([
            'name' => $this->typeName() . 'UpdateInputType',
            'fields' => $fields
        ]);
    }
}