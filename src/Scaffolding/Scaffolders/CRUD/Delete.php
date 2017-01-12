<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataList;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Exception;

/**
 * A generic delete operation.
 */
class Delete extends MutationScaffolder implements CRUDInterface
{
    use DataObjectTypeTrait;

    /**
     * DeleteOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'delete'.ucfirst($this->typeName()),
            $this->typeName()
        );

        $this->setResolver(function ($object, array $args, $context, $info) {
            $results = DataList::create($this->dataObjectClass)
                ->byIDs($args['IDs']);

            foreach ($results as $obj) {
                if ($obj->canDelete($context['currentMember'])) {
                    $obj->delete();
                } else {
                    throw new Exception(sprintf(
                        'Cannot delete %s with ID %s',
                        $this->dataObjectClass,
                        $obj->ID
                    ));
                }
            }
        });
    }

    /**
     * @return string`
     */
    public function getIdentifier()
    {
        return SchemaScaffolder::DELETE;
    }

    /**
     * @return array
     */
    protected function createArgs()
    {
        return [
            'IDs' => [
                'type' => Type::nonNull(Type::listOf(Type::id())),
            ],
        ];
    }
}
