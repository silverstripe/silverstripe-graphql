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
use SilverStripe\GraphQL\Serialisation\SerialisableInputType;
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
        $this->setResolverFactory(CreateResolverFactory::create($this->getDataObjectClass()));
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
        return new SerialisableInputType([
            'name' => $this->inputTypeName(),
            'fields' => function () use ($manager) {
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
        return $this->getTypeName() . 'CreateInputType';
    }

}
