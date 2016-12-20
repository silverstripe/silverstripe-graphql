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
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Scaffolding\Creators\DataObjectTypeCreator;
use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use SilverStripe\Core\ClassInfo;

/**
 * Scaffolds a DataObjectTypeCreator
 */
class DataObjectScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    use DataObjectTypeTrait;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var  SilverStripe\GraphQL\Scaffolding\OperationList
     */
    protected $operations;

    /**
     * @var  SilverStripe\GraphQL\Scaffolding\OperationList     
     */
    protected $nestedQueries;

    /**
     * DataObjectScaffold constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        if (!class_exists($dataObjectClass)) {
            throw new InvalidArgumentException(sprintf(
                'DataObjectScaffold instantiated with non-existent classname "%s"',
                $dataObjectClass
            ));
        }

        if (!is_subclass_of($dataObjectClass, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'DataObjectScaffold must instantiate with a classname that is a subclass of %s (%s given)',
                DataObject::class,
                $dataObjectClass
            ));
        }

        $this->fields = ArrayList::create([]);
        $this->operations = OperationList::create([]);
        $this->nestedQueries = OperationList::create([]);

        $this->dataObjectClass = $dataObjectClass;
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
     * Adds all db fields, and optionally has_one
     * 
     * @param boolean $includeHasOne
     * @return  $this
     */
    public function addAllFields($includeHasOne = false)
    {
    	$fields = $this->allFieldsFromDataObject($includeHasOne);

    	return $this->addFields($fields);
    }

    /**
     * Adds fields against a blacklist
     * 
     * @param array   $exclusions
     * @param boolean $includeHasOne
     * @return  $this
     */
    public function addAllFieldsExcept(array $exclusions, $includeHasOne = false)
    {
    	$fields = $this->allFieldsFromDataObject($includeHasOne);
    	$filteredFields = array_diff($fields, $exclusions);

    	return $this->addFields($filteredFields);
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
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return OperationList
     */
    public function getNestedQueries()
    {
    	return $this->nestedQueries;
    }

    /**
     * Removes an operation
     * @param $name
     * @return $this
     */
    public function removeOperation($identifier)
    {
        $this->operations->removeByName($identifier);

        return $this;
    }

    public function nestedQuery($fieldName)
    {
    	$query = $this->nestedQueries->findByName($fieldName);

    	if($query) {
    		return $query;
    	}

    	$result = $this->getDataObjectInstance()->obj($fieldName);

        if (!$result instanceof DataList && !$result instanceof ArrayList) {
        	throw new InvalidArgumentException(sprintf(
        		'%s::addNestedQuery() tried to add %s, but must be passed a method name or relation that returns a DataList or ArrayList',
        		__CLASS__,
        		$fieldName
        	));
        }

        $typeName = ScaffoldingUtil::typeNameForDataObject($result->dataClass());

        $queryScaffolder = new QueryScaffolder(
        	$fieldName,
        	$typeName,
        	function ($obj) use ($fieldName) {        		
        		return $obj->obj($fieldName);
        	}
        );

        $this->nestedQueries->push($queryScaffolder);
    }

    /**
     * Find or make an operation
     * @param int $identifier
     * 
     * @return OperationScaffolder
     */
    public function operation($identifier)
    {
        $op = $this->operations->findByName($identifier);

        if ($op) {
            return $op;
        }

        if ($scaffoldClass = self::getOperationScaffoldForName($identifier)) {
            $operationScaffold = new $scaffoldClass($this->dataObjectClass);
        } else {
        	throw new InvalidArgumentException(sprintf(
        		'Invalid operation: %s added to %s',
        		$identifier,
        		$this->dataObjectClass
        	));
        }

        $this->operations->push($operationScaffold);

        return $operationScaffold;
    }

    /**
     * Puts all of the configuration into a type creator
     * @return DataObjectTypeCreator
     */
    public function getCreator(Manager $manager)
    {
        $fieldMap = [];
        $instance = $this->getDataObjectInstance();
        $extraDataObjects = $this->nestedDataObjectTypes();
        $fields = array_unique($this->fields->toArray());

        if (empty($fields)) {
        	$fields = Config::inst()->get(self::class, 'default_fields');        	
        }

        foreach ($fields as $fieldName) {
            $result = $instance->obj($fieldName);
            if ($result instanceof DataList || $result instanceof ArrayList) {
                throw new InvalidArgumentException(sprintf(
                	'Fieldname %s added to %s returns a list. This should be defined as a nested query using addNestedQuery(%s)',
                	$fieldName,
                	$this->dataObjectClass,
                	$fieldName
                ));
            }

            if ($result instanceof DBField) {
                $typeName = $result->config()->graphql_type;
                $fieldMap[$fieldName] = (new TypeParser($typeName))->toArray();
            } else if(is_bool($result)) {
            	$fieldMap[$fieldName] = ['type' => Type::boolean()];
            } else if(is_int($result)) {
            	$fieldMap[$fieldName] = ['type' => Type::int()];
            } else if(is_float($result)) {
            	$fieldMap[$fieldName] = ['type' => Type::float()];
            } else {
            	$fieldMap[$fieldName] = ['type' => Type::string()];
            }
        }

        foreach ($extraDataObjects as $fieldName => $className) {
            $result = $instance->obj($fieldName);
            $typeName = ScaffoldingUtil::typeNameForDataObject($className);

            $fieldMap[$fieldName] = [
                'type' => function () use ($manager, $typeName) {
                    return $manager->getType($typeName);
                }
            ];
        }

        foreach ($this->nestedQueries as $scaffolder) {
        	$creator = $scaffolder->getCreator($manager);
        	$fieldMap[$scaffolder->getName()] = $creator->toArray();
        }

        return new DataObjectTypeCreator($manager, $this->typeName(), $fieldMap);
    }

    /**
     * Gets types for all ancestors of this class that will need to be added
     * @return array
     */
    public function getDependentTypes()
    {    	
    	return array_merge(
    		$this->ancestralTypes(),
    		array_values($this->nestedDataObjectTypes()),
    		array_values($this->nestedConnections())
    	);
    }	


    /**
     * Adds the type to the Manager
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $creator = $this->getCreator($manager);
        if (!$manager->hasType($this->typeName())) {
            $manager->addType($creator->toType(), $this->typeName());
        }

        foreach($this->operations as $op) {
        	$op->addToManager($manager);
        }
    }

    /**
     * @param  boolean $includeHasOne
     * @return array
     */
    protected function allFieldsFromDataObject($includeHasOne = false)
    {
    	$fields = [];
    	$db = DataObject::config()->fixed_fields;
    	$db = array_merge($db, Config::inst()->get($this->dataObjectClass, 'db', Config::INHERITED));

    	foreach($db as $fieldName => $type) {
    		$fields[] = $fieldName;
    	}

    	if($includeHasOne) {    		
    		$hasOne = $this->getDataObjectInstance()->hasOne();
    		foreach($hasOne as $fieldName => $class) {
    			$fields[] = $fieldName;
    		}
    	}

    	return $fields;
    }

    /**
     * Gets any DataObjects that are implicitly required by this type definition, e.g. has_one, has_many
     * @return array
     */
    protected function nestedDataObjectTypes()
    {
        $types = [];
        $instance = $this->getDataObjectInstance();
        $fields = $this->fields->toArray();

        foreach ($fields as $fieldName) {
            $result = $instance->obj($fieldName);
            if ($result instanceof DataObjectInterface) {
                $types[$fieldName] = get_class($result);
            }
        }

        return $types;
    }

    protected function nestedConnections()
    {
    	$queries = [];
    	foreach($this->nestedQueries as $q) {
    		$queries[$q->getName()] = $this->getDataObjectInstance()
    			->obj($q->getName())
    			->dataClass();
    	}

    	return $queries;
    }


    protected function ancestralTypes()
    {
    	$types = [];
    	$ancestry = array_reverse(ClassInfo::ancestry($this->dataObjectClass));
    	
    	foreach($ancestry as $class) {    		
    		if($class == DataObject::class) break;
    		$types[] = $class;
    	}

    	return $types;
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