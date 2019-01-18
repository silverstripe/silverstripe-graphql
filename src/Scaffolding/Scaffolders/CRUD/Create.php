<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Schema\Components\Argument;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Input;
use SilverStripe\GraphQL\Schema\Components\TypeReference;
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
            new Argument(
                'Input',
                TypeReference::create($this->inputTypeName())
                    ->setRequired(true)
            ),
        ];
    }

    /**
     * @param Manager $manager
     * @return Input
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
            $fields[] = new Field(
                $dbFieldName,
                $result->getGraphQLType($manager)
            );
        }


        return new Input(
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
