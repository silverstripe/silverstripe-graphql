<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\TypeAbstractions\ArgumentAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InputTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InternalType;
use SilverStripe\GraphQL\TypeAbstractions\ReferentialTypeAbstraction;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Scaffolds a generic update operation for DataObjects.
 */
class Update extends MutationScaffolder implements CRUDInterface
{
    /**
     * Update constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, null, $dataObjectClass);
        $this->setResolverFactory(UpdateResolverFactory::create(['dataObjectClass' => $this->getDataObjectClass()]));
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        return 'update' . ucfirst($this->getTypeName());
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
            new ArgumentAbstraction(
                'Input',
                (new ReferentialTypeAbstraction($this->inputTypeName()))
                    ->setRequired(true)
            ),
        ];
    }

    /**
     * Based on the args provided, create an Input type to add to the Manager.
     * @param Manager $manager
     * @return InputObjectType
     */
    protected function generateInputType(Manager $manager)
    {
        $fields = [
            new FieldAbstraction(
                'ID',
                InternalType::id()->setRequired(true)
            ),
        ];
        $instance = $this->getDataObjectInstance();

        // Setup default input args.. Placeholder!
        $schema = Injector::inst()->get(DataObjectSchema::class);
        $db = $schema->fieldSpecs($this->getDataObjectClass());

        unset($db['ID']);

        foreach ($db as $dbFieldName => $dbFieldType) {
            /** @var DBField|TypeCreatorExtension $result */
            $result = $instance->obj($dbFieldName);
            // Skip complex fields, e.g. composite, as that would require scaffolding a new input type.
            if (!$result->isInternalGraphQLType()) {
                continue;
            }
            $fields[] = new FieldAbstraction(
                $dbFieldName,
                $result->getGraphQLType($manager)
            );
        }

        return new InputTypeAbstraction(
            $this->inputTypeName(),
            $fields
        );
    }

    /**
     * @return string
     */
    protected function inputTypeName()
    {
        return $this->getTypeName() . 'UpdateInputType';
    }

}
