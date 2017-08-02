<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\ORM\DataList;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Exception;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Scaffolds a generic update operation for DataObjects.
 */
class Update extends MutationScaffolder implements CRUDInterface
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
            $this->typeName()
        );

        // Todo: this is totally half baked
        $this->setResolver(function ($object, array $args, $context, $info) {
            $obj = DataList::create($this->dataObjectClass)
                ->byID($args['ID']);
            if (!$obj) {
                throw new Exception(sprintf(
                    '%s with ID %s not found',
                    $this->dataObjectClass,
                    $args['ID']
                ));
            }

            if ($obj->canEdit($context['currentUser'])) {
                $obj->update($args['Input']);
                $obj->write();

                return $obj;
            } else {
                throw new Exception(sprintf(
                    'Cannot edit this %s',
                    $this->dataObjectClass
                ));
            }
        });
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return SchemaScaffolder::UPDATE;
    }

    /**
     * Use a generated Input type, and require an ID.
     *
     * @return array
     */
    protected function createArgs()
    {
        return [
            'ID' => [
                'type' => Type::nonNull(Type::id())
            ],
            'Input' => [
                'type' => Type::nonNull($this->generateInputType()),
            ],
        ];
    }

    /**
     * Based on the args provided, create an Input type to add to the Manager.
     *
     * @return InputObjectType
     */
    protected function generateInputType()
    {
        return new InputObjectType([
            'name' => $this->typeName().'UpdateInputType',
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
            }
        ]);
    }
}
