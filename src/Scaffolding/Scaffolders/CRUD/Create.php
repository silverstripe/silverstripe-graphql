<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\Core\Extensible;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;
use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;
use GraphQL\Type\Definition\Type;

/**
 * A generic "create" operation for a DataObject.
 */
class Create extends MutationScaffolder
{
    use DataObjectTypeTrait;
    use Extensible;

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
                    $results = $this->extend('augmentMutation', $newObject, $args, $context, $info);
                    // Extension points that return false should kill the create
                    if (in_array(false, $results, true)) {
                        return;
                    }

                     return DataObject::get_by_id($this->dataObjectClass, $newObject->ID);
                } else {
                    throw new Exception("Cannot create {$this->dataObjectClass}");
                }
            }
        );
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $manager->addType($this->generateInputType($manager));
        parent::addToManager($manager);
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createArgs(Manager $manager)
    {
        $args = [
            'Input' => [
                'type' => Type::nonNull($manager->getType($this->inputTypeName())),
            ],
        ];
        $this->extend('updateArgs', $args, $manager);

        return $args;
    }

    /**
     * @param Manager $manager
     * @return InputObjectType
     */
    protected function generateInputType(Manager $manager)
    {
        return new InputObjectType([
            'name' => $this->inputTypeName(),
            'fields' => function () use ($manager) {
                $fields = [];
                $instance = $this->getDataObjectInstance();

                // Setup default input args.. Placeholder!
                $schema = Injector::inst()->get(DataObjectSchema::class);
                $db = $schema->fieldSpecs($this->dataObjectClass);

                unset($db['ID']);

                foreach ($db as $dbFieldName => $dbFieldType) {
                    /** @var DBField|TypeCreatorExtension $result */
                    $result = $instance->obj($dbFieldName);
                    // Skip complex fields, e.g. composite, as that would require scaffolding a new input type.
                    if (!$result->isInternalGraphQLType()) {
                        continue;
                    }
                    $arr = [
                        'type' => $result->getGraphQLType($manager),
                    ];
                    $fields[$dbFieldName] = $arr;
                }

                return $fields;
            },
        ]);
    }

    /**
     * @return string
     */
    protected function inputTypeName()
    {
        return $this->typeName().'CreateInputType';
    }
}
