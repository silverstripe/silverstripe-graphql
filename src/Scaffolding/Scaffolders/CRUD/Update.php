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
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Scaffolds a generic update operation for DataObjects.
 */
class Update extends MutationScaffolder implements ResolverInterface
{
    use DataObjectTypeTrait;

    /**
     * UpdateOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'update'.ucfirst($this->typeName()),
            $this->typeName(),
            $this
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
     * Use a generated Input type, and require an ID.
     *
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            'Input' => [
                'type' => Type::nonNull($manager->getType($this->inputTypeName())),
            ],
        ];
    }

    /**
     * Based on the args provided, create an Input type to add to the Manager.
     * @param Manager $manager
     * @return InputObjectType
     */
    protected function generateInputType(Manager $manager)
    {
        return new InputObjectType([
            'name' => $this->inputTypeName(),
            'fields' => function () use ($manager) {
                $fields = [
                    'ID' => [
                        'type' => Type::nonNull(Type::id()),
                    ],
                ];
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
            }
        ]);
    }

    /**
     * @return string
     */
    protected function inputTypeName()
    {
        return $this->typeName().'UpdateInputType';
    }

    public function resolve($object, $args, $context, $info)
    {
        $input = $args['Input'];
        $obj = DataList::create($this->dataObjectClass)
            ->byID($input['ID']);
        if (!$obj) {
            throw new Exception(sprintf(
                '%s with ID %s not found',
                $this->dataObjectClass,
                $input['ID']
            ));
        }
        unset($input['ID']);
        if (!$obj->canEdit($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot edit this %s',
                $this->dataObjectClass
            ));
        }

        // Extension points that return false should kill the write operation
        $results = $this->extend('augmentMutation', $obj, $args, $context, $info);
        if (in_array(false, $results, true)) {
            return $obj;
        }

        $obj->update($input);
        $obj->write();
        return $obj;
    }
}