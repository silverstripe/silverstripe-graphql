<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Schema\Components\ArgumentAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InputTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeReference;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A generic "create" operation for a DataObject.
 */
class Create extends MutationScaffolder implements CRUDInterface
{
    /**
     * Create constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, null, $dataObjectClass);
        $this->setResolverFactory(CreateResolverFactory::create(['dataObjectClass' => $this->getDataObjectClass()]));
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

        return 'create' . ucfirst($this->getTypeName());
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
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            new ArgumentAbstraction(
                'Input',
                TypeReference::create($this->inputTypeName())
                    ->setRequired(true)
            ),
        ];
    }

    /**
     * @param Manager $manager
     * @return InputTypeAbstraction
     */
    protected function generateInputType(Manager $manager)
    {
        $fields = [];
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
            null,
            $fields
        );
    }

    /**
     * @return string
     */
    protected function inputTypeName()
    {
        return $this->getTypeName() . 'CreateInputType';
    }

}
