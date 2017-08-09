<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A generic "create" operation for a DataObject.
 */
class Create extends MutationScaffolder implements CRUDInterface
{
    use DataObjectTypeTrait;

    /**
     * CreateOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'create'.ucfirst($this->typeName()),
            $this->typeName(),
            function ($object, array $args, $context, $info) {
                // Todo: this is totally half baked
                if (singleton($this->dataObjectClass)->canCreate($context['currentUser'], $context)) {
                    /** @var DataObject $newObject */
                    $newObject = Injector::inst()->create($this->dataObjectClass);
                    $newObject->update($args['Input']);
                    $newObject->write();
                    
                    return DataObject::get_by_id($this->dataObjectClass, $newObject->ID);
                } else {
                    throw new Exception("Cannot create {$this->dataObjectClass}");
                }
            }
        );
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return SchemaScaffolder::CREATE;
    }

    /**
     * @return array
     */
    protected function createArgs()
    {
        return [
            'Input' => [
                'type' => Type::nonNull($this->generateInputType()),
            ],
        ];
    }

    /**
     * @return InputObjectType
     */
    protected function generateInputType()
    {
        return new InputObjectType([
            'name' => $this->typeName().'CreateInputType',
            'fields' => function () {
                $fields = [];
                $instance = $this->getDataObjectInstance();

                // Setup default input args.. Placeholder!
                $schema = Injector::inst()->get(DataObjectSchema::class);
                $db = $schema->fieldSpecs($this->dataObjectClass);

                unset($db['ID']);

                foreach ($db as $dbFieldName => $dbFieldType) {
                    /** @var DBField $result */
                    $result = $instance->obj($dbFieldName);
                    $arr = [
                        'type' => $result->getGraphQLType(),
                    ];
                    $fields[$dbFieldName] = $arr;
                }

                return $fields;
            },
        ]);
    }
}
