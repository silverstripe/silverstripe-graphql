<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\GraphQL\Scaffolding\Interfaces\Configurable;

/**
 * Scaffolds a DataObjectTypeCreator.
 */
class DataObjectScaffolder implements ManagerMutatorInterface, ScaffolderInterface, Configurable
{
    use DataObjectTypeTrait;
    use Chainable;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var SilverStripe\GraphQL\Scaffolding\OperationList
     */
    protected $operations;

    /**
     * @var SilverStripe\GraphQL\Scaffolding\OperationList
     */
    protected $nestedQueries;

    /**
     * DataObjectScaffold constructor.
     *
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
     *
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
     *
     * @return mixed
     */
    public function addField($field)
    {
        return $this->addFields((array) $field);
    }

    /**
     * Adds all db fields, and optionally has_one.
     *
     * @param bool $includeHasOne
     *
     * @return $this
     */
    public function addAllFields($includeHasOne = false)
    {
        $fields = $this->allFieldsFromDataObject($includeHasOne);

        return $this->addFields($fields);
    }

    /**
     * Adds fields against a blacklist.
     *
     * @param array $exclusions
     * @param bool  $includeHasOne
     *
     * @return $this
     */
    public function addAllFieldsExcept(array $exclusions, $includeHasOne = false)
    {
        $fields = $this->allFieldsFromDataObject($includeHasOne);
        $filteredFields = array_diff($fields, $exclusions);

        return $this->addFields($filteredFields);
    }

    /**
     * @param $field
     *
     * @return $this
     */
    public function removeField($field)
    {
        $this->fields->remove($field);

        return $this;
    }

    /**
     * @param array $fields
     *
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
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * @return OperationList
     */
    public function getNestedQueries()
    {
    	return $this->nestedQueries;
    }

    /**
     * Removes an operation.
     *
     * @param $name
     *
     * @return $this
     */
    public function removeOperation($identifier)
    {        
        $this->operations->removeByIdentifier($identifier);

        return $this;
    }

    /**
     * Find or make an operation.
     *
     * @param string $operation
     *
     * @return OperationScaffolder
     */
    public function operation($operation)
    {	
        $scaffoldClass = OperationScaffolder::getOperationScaffoldFromIdentifier($operation);

        if (!$scaffoldClass) {
            throw new InvalidArgumentException(sprintf(
                'Invalid operation: %s added to %s',
                $operation,
                $this->dataObjectClass
            ));
        }

        $scaffolder = new $scaffoldClass($this->dataObjectClass);
        $existing = $this->operations->findByIdentifier($operation);

        if ($existing) {        
            return $existing;
        }

        $this->operations->push(
            $scaffolder->setChainableParent($this)
        );

        return $scaffolder;
    }


    /**
     * Finds or adds a nested query, e.g. has_many/many_many relation.
     *
     * @param string $fieldName
     *
     * @return OperationScaffolder
     */
    public function nestedQuery($fieldName)
    {
        $query = $this->nestedQueries->findByName($fieldName);

        if ($query) {
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

        $queryScaffolder = (new QueryScaffolder(
            $fieldName,
            $typeName,
            function ($obj) use ($fieldName) {
                return $obj->obj($fieldName);
            }
        ))->setChainableParent($this);

        $this->nestedQueries->push($queryScaffolder);

        return $queryScaffolder;
    }

    /**
     * Find or make an operation.
     *
     * @param string $operation
     *
     * @return OperationScaffolder
     */
    public function operation($operation)
    {
        $scaffoldClass = OperationScaffolder::getOperationScaffoldFromIdentifier($operation);

        if (!$scaffoldClass) {
            throw new InvalidArgumentException(sprintf(
                'Invalid operation: %s added to %s',
                $operation,
                $this->dataObjectClass
            ));
        }

        $operation = new $scaffoldClass($this->dataObjectClass);
        $existing = $this->operations->findByType(get_class($operation));

        if ($existing) {
            return $existing;
        }

        $this->operations->push(
            $operation->setChainableParent($this)
        );

        return $operation;
    }

    /**
     * Gets types for all ancestors of this class that will need to be added.
     *
     * @return array
     */
    public function getDependentClasses()
    {
        return array_merge(
            array_values($this->nestedDataObjectClasses()),
            array_values($this->nestedConnections())
        );
    }

    /**
     * Gets the class ancestry back to DataObject.
     *
     * @return array
     */
    public function getAncestralClasses()
    {
        $classes = [];
        $ancestry = array_reverse(ClassInfo::ancestry($this->dataObjectClass));

        foreach ($ancestry as $class) {
            if ($class == DataObject::class) {
                break;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    public function applyConfig(array $config)
    {
        if (empty($config['fields']) || !is_array($config['fields'])) {
            throw new \Exception(
                "No array of fields defined for $dataObjectClass"
            );
        }

        $this->addFields($config['fields']);

        if (isset($config['fieldsExcept'])) {
            if (!is_array($config['fieldsExcept'])) {
                throw new InvalidArgumentException(sprintf(
                    '"fieldsExcept" must be an array. See %s',
                    $this->dataObjectClass
                ));
            }

            $this->addAllFieldsExcept($config['fieldsExcept']);
        }

        if (!isset($config['operations'])) {
            return $this;
        }

        if ($config['operations'] === '*') {
            $config['operations'] = [
                GraphQLScaffolder::CREATE => true,
                GraphQLScaffolder::READ => true,
                GraphQLScaffolder::UPDATE => true,
                GraphQLScaffolder::DELETE => true,
            ];
        }

        if (!ArrayLib::is_associative($config['operations'])) {
            throw new \Exception(
                'Operations field must be a map of operation names to a map of settings, or true/false'
            );
        }

        foreach ($config['operations'] as $opID => $opSettings) {
            if ($opSettings === false) {
                continue;
            }

            $this->operation($opID)
                ->applyConfig((array) $opSettings);
        }

        if (isset($config['nestedQueries'])) {
            if (!ArrayLib::is_associative($config['nestedQueries'])) {
                throw new InvalidArgumentException(sprintf(
                    '"nestedQueries" must be a map of relation name to a map of settings, or true/false. See %s',
                    $this->dataObjectClass
                ));
            }

            foreach ($config['nestedQueries'] as $relationName => $settings) {
                if ($settings === false) {
                    continue;
                }
                $queryScaffold = $this->nestedQuery($relationName)
                    ->applyConfig((array) $settings);
            }
        }

        return $this;
    }

    /**
     * @param Manager $manager
     *
     * @return ObjectType
     */
    public function scaffold(Manager $manager)
    {
        return new ObjectType([
            'name' => $this->typeName(),
            'fields' => $this->createFields($manager),
        ]);
    }

    /**
     * Adds the type to the Manager.
     *
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $scaffold = $this->scaffold($manager);
        if (!$manager->hasType($this->typeName())) {
            $manager->addType($scaffold, $this->typeName());
        }

        foreach ($this->operations as $op) {
            $op->addToManager($manager);
        }
    }

    /**
     * @param bool $includeHasOne
     *
     * @return array
     */
    protected function allFieldsFromDataObject($includeHasOne = false)
    {
        $fields = [];
        $db = DataObject::config()->fixed_fields;
        $db = array_merge($db, Config::inst()->get($this->dataObjectClass, 'db', Config::INHERITED));

        foreach ($db as $fieldName => $type) {
            $fields[] = $fieldName;
        }

        if ($includeHasOne) {
            $hasOne = $this->getDataObjectInstance()->hasOne();
            foreach ($hasOne as $fieldName => $class) {
                $fields[] = $fieldName;
            }
        }

        return $fields;
    }

    /**
     * Gets any DataObjects that are implicitly required by this type definition, e.g. has_one, has_many.
     *
     * @return array
     */
    protected function nestedDataObjectClasses()
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
        foreach ($this->nestedQueries as $q) {
            $queries[$q->getName()] = $this->getDataObjectInstance()
                ->obj($q->getName())
                ->dataClass();
        }

        return $queries;
    }

    protected function createFields(Manager $manager)
    {
        $fieldMap = [];
        $instance = $this->getDataObjectInstance();
        $extraDataObjects = $this->nestedDataObjectClasses();
        $fields = array_unique($this->fields->toArray());

        if (empty($fields)) {
            $fields = Config::inst()->get(self::class, 'default_fields');
        }

        $resolver = function ($obj, $args, $context, $info) {
            return $obj->obj($info->fieldName);
        };

        foreach ($fields as $fieldName) {
            if (!ScaffoldingUtil::isValidFieldName($instance, $fieldName)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid field "%s" on %s',
                    $fieldName,
                    $this->dataObjectClass
                ));
            }

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
                $fieldMap[$fieldName]['resolve'] = $resolver;
            }
        }

        foreach ($extraDataObjects as $fieldName => $className) {
            $result = $instance->obj($fieldName);
            $typeName = ScaffoldingUtil::typeNameForDataObject($className);

            $fieldMap[$fieldName] = [
                'type' => function () use ($manager, $typeName) {
                    return $manager->getType($typeName);
                },
                'resolve' => $resolver,
            ];
        }

        foreach ($this->nestedQueries as $scaffolder) {
            $scaffold = $scaffolder->scaffold($manager);
            $fieldMap[$scaffolder->getName()] = $scaffold;
        }

        return $fieldMap;
    }
}
