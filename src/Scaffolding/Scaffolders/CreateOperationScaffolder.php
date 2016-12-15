<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\GraphQL\Manager;

/**
 * A generic "create" operation for a DataObject
 */
class CreateOperationScaffolder extends MutationScaffolder
{

    use DataObjectTypeTrait;

    /**
     * CreateOperationScaffolder constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'create' . ucfirst($this->typeName()),
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
        unset($args['ID']);

        $this->args = $args;

        // Todo: this is totally half baked
        $this->setResolver(function ($object, array $args, $context, $info) {
            if (singleton($this->dataObjectName)->canCreate()) {
                $newObject = Injector::inst()->createWithArgs(
                    $this->dataObjectName,
                    $args
                );
                $newObject->write();

                return $newObject;
            } else {
                // somehow deal with permission errors here
            }
        });

    }

    /**
     * @return array
     */
    protected function createArgs()
    {
        return [
            'Input' => [
                'type' => Type::nonNull($this->generateInputType())
            ]
        ];
    }

    /**
     * @return InputObjectType
     */
    protected function generateInputType()
    {
        $fields = [];
        foreach ($this->args as $fieldName => $typeStr) {
            $arr = (new TypeParser($typeStr))->toArray();
            $arr['name'] = $fieldName;
            $fields[] = $arr;
        }
        return new InputObjectType([
            'name' => $this->typeName() . 'CreateInputType',
            'fields' => $fields
        ]);

    }
}