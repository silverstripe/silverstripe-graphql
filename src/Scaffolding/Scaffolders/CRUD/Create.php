<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A generic "create" operation for a DataObject.
 */
class Create extends MutationScaffolder implements ResolverInterface
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
            'create' . ucfirst($this->typeName()),
            $this->typeName(),
            $this,
            $dataObjectClass
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

    protected function createDefaultArgs(Manager $manager)
    {
        return [
            'Input' => [
                'type' => Type::nonNull($manager->getType($this->inputTypeName())),
            ]
        ];
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
        return $this->typeName() . 'CreateInputType';
    }

    public function resolve($object, $args, $context, $info)
    {
        // Todo: this is totally half baked
        $singleton = DataObject::singleton($this->dataObjectClass);
        if (!$singleton->canCreate($context['currentUser'], $context)) {
            throw new Exception("Cannot create {$this->dataObjectClass}");
        }

        /** @var DataObject $newObject */
        $newObject = Injector::inst()->create($this->dataObjectClass);
        $newObject->update($args['Input']);

        // Extension points that return false should kill the create
        $results = $this->extend('augmentMutation', $newObject, $args, $context, $info);
        if (in_array(false, $results, true)) {
            return null;
        }

        // Save and return
        $newObject->write();
        return DataObject::get_by_id($this->dataObjectClass, $newObject->ID);
    }
}
