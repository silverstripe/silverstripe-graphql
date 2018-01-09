<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\Core\Extensible;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataList;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A generic delete operation.
 */
class Delete extends MutationScaffolder implements CRUDInterface
{
    use DataObjectTypeTrait;
    use Extensible;

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
            DB::get_conn()->withTransaction(function () use ($args, $context) {
                $results = DataList::create($this->dataObjectClass)
                    ->byIDs($args['IDs']);
                $this->extend('onBeforeMutation', $results, $args, $context, $info);
                foreach ($results as $obj) {
                    /** @var DataObject $obj */
                    if ($obj->canDelete($context['currentUser'])) {
                        $obj->delete();
                    } else {
                        throw new Exception(sprintf(
                            'Cannot delete %s with ID %s',
                            $this->dataObjectClass,
                            $obj->ID
                        ));
                    }
                }
                $this->extend('onAfterMutation', $args, $context, $info);
            });
        });
    }

    /**
     * @return string
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
        $args = [
            'IDs' => [
                'type' => Type::nonNull($this->generateInputType()),
            ],
        ];
        $this->extend('updateArgs', $args);

        return $args;
    }

    protected function generateInputType()
    {
        return Type::listOf(Type::id());
    }
}
