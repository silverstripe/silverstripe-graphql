<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\OperationList;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\GraphQL\Scaffolding\Creators\DataObjectTypeCreator;
use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\Core\Config\Config;
/**
 * Scaffolds a DataObjectTypeCreator
 * @package SilverStripe\GraphQL\Scaffolding
 */
class DataObjectScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    use DataObjectTypeTrait;

    /**
     * @var OperationList
     */
    protected $queries;

    /**
     * @var  OperationList
     */
    protected $mutations;

    /**
     * @var array
     */
    protected $fields;

    /**
     * DataObjectScaffold constructor.
     * @param $dataObjectName
     */
    public function __construct($dataObjectName)
    {
        if (!class_exists($dataObjectName)) {
            throw new InvalidArgumentException(sprintf(
                'DataObjectScaffold instantiated with non-existent classname "%s"',
                $dataObjectName
            ));
        }

        if (!is_subclass_of($dataObjectName, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'DataObjectScaffold must instantiate with a classname that is a subclass of %s',
                DataObject::class
            ));
        }

        $this->queries = OperationList::create([]);
        $this->mutations = OperationList::create([]);
        $this->fields = ArrayList::create([]);

        $this->dataObjectName = $dataObjectName;
    }

    /**
     * Find or make a query
     * @param $name
     * @param null $resolver
     * @return bool|QueryScaffolder
     */
    public function query($name, $resolver = null)
    {
        $query = $this->queries->findByName($name);

        if ($query) {
            return $query;
        }

        if ($scaffoldClass = self::getOperationScaffoldForName($name)) {
            $operationScaffold = new $scaffoldClass($this->dataObjectName);
        } else {
            $operationScaffold = new QueryScaffolder(
                $name,
                $this->typeName(),
                $resolver
            );
        }

        $this->queries->push($operationScaffold);

        return $operationScaffold;
    }


    /**
     * Find or make a mutation
     * @param $name
     * @param null $resolver
     * @return bool|MutationScaffolder
     */
    public function mutation($name, $resolver = null)
    {
        $mutation = $this->mutations->findByName($name);

        if ($mutation) {
            return $mutation;
        }

        if ($scaffoldClass = self::getOperationScaffoldForName($name)) {
            $operationScaffold = new $scaffoldClass($this->dataObjectName);
        } else {
            $operationScaffold = new MutationScaffolder(
                $name,
                $this->typeName(),
                $resolver
            );
        }

        $this->mutations->push($operationScaffold);

        return $operationScaffold;
    }

    /**
     * Removes a mutation
     * @param $name
     * @return $this
     */
    public function removeMutation($name)
    {
        $this->mutations->removeByName($name);

        return $this;
    }


    /**
     * Removes a query
     * @param $name
     * @return $this
     */
    public function removeQuery($name)
    {
        $this->queries->removeByName($name);

        return $this;
    }

    /**
     * Adds visible fields.
     * @param array $fields
     */
    public function addFields(array $fields)
    {
        // Remove duplicates
        $this->fields = ArrayList::create(array_unique(array_merge(
            $this->fields->toArray(),
            $fields
        )));

        return $this;
    }

    /**
     * @param $field
     * @return mixed
     */
    public function addField($field)
    {
        return $this->addFields((array)$field);
    }

    /**
     * @param $field
     * @return $this
     */
    public function removeField($field)
    {
        $this->fields->remove($field);

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function removeFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->removeField($field);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @return array
     */
    public function getMutations()
    {
        return $this->mutations;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Puts all of the configuration into a type creator
     * @return DataObjectTypeCreator
     */
    public function getCreator(Manager $manager)
    {
        $fieldMap = [];
        $instance = $this->getDataObjectInstance();
        $extraDataObjects = $this->getExtraDataObjects();
        $fields = array_unique($this->fields->toArray());

        if (empty($fields)) {
        	$fields = Config::inst()->get(self::class, 'default_fields');        	
        }

        foreach ($fields as $fieldName) {
            $result = $instance->obj($fieldName);
            if ($result instanceof DBField) {
                $typeName = $result->config()->graphql_type;
                $fieldMap[$fieldName] = (new TypeParser($typeName))->toArray();
            }
        }

        foreach ($extraDataObjects as $fieldName => $className) {
            $result = $instance->obj($fieldName);
            $typeName = $this->typeNameForDataObject($className);

            if ($result instanceof DataList || $result instanceof ArrayList) {
                $fieldMap[$fieldName] = [
                    'type' => function () use ($manager, $typeName) {
                        return Type::listOf($manager->getType($typeName));
                    }
                ];
            } else {
                $fieldMap[$fieldName] = [
                    'type' => function () use ($manager, $typeName) {
                        return $manager->getType($typeName);
                    }
                ];
            }
        }

        return new DataObjectTypeCreator($manager, $this->typeName(), $fieldMap);
    }


    /**
     * Gets any DataObjects that are implicitly required by this type definition, e.g. has_one, has_many
     * @return array
     */
    public function getExtraDataObjects()
    {
        $types = [];
        $instance = $this->getDataObjectInstance();
        $fields = $this->fields->toArray();

        foreach ($fields as $fieldName) {
            $result = $instance->obj($fieldName);
            if ($result instanceof DataList || $result instanceof ArrayList) {
                $types[$fieldName] = $result->dataClass();
            } else {
                if ($result instanceof DataObjectInterface) {
                    $types[$fieldName] = get_class($result);
                }
            }
        }

        return $types;
    }


    /**
     * Adds the type to the Manager
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $creator = $this->getCreator($manager);

        if (!$manager->getType($this->typeName())) {
            $manager->addType($creator->toType(), $this->typeName());
        }

        foreach ($this->queries as $op) {
            $op->addToManager($manager);
        }

        foreach ($this->mutations as $op) {
            $op->addToManager($manager);
        }

    }


    /**
     * @param $name
     * @return null
     */
    protected static function getOperationScaffoldForName($name)
    {
        switch ($name) {
            case GraphQLScaffolder::CREATE:
                return CreateOperationScaffolder::class;
            case GraphQLScaffolder::READ:
                return ReadOperationScaffolder::class;
            case GraphQLScaffolder::UPDATE:
                return UpdateOperationScaffolder::class;
            case GraphQLScaffolder::DELETE:
                return DeleteOperationScaffolder::class;
        }

        return null;
    }

}