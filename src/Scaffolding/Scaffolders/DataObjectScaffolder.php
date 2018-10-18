<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder\FieldDefinition;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * Scaffolds a DataObjectTypeCreator.
 */
class DataObjectScaffolder implements ManagerMutatorInterface, ScaffolderInterface, ConfigurationApplier
{
    use DataObjectTypeTrait;
    use Chainable;
    use Extensible;

    /**
     * Minimum fields that any type will expose. Useful for implicitly
     * created types, e.g. exposing a has_one.
     *
     * @config
     * @var array
     */
    private static $default_fields = [
        'ID' => 'ID',
    ];

    /**
     * @var ArrayList|FieldDefinition[]
     */
    protected $fields;

    /**
     * @var OperationList
     */
    protected $operations;

    /**
     * @var OperationList
     */
    protected $nestedQueries = [];

    /**
     * DataObjectScaffold constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->fields = ArrayList::create([]);
        $this->operations = OperationList::create([]);
        $this->setDataObjectClass($dataObjectClass);
    }

    /**
     * Name of graphql type
     *
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName();
    }

    /**
     * Adds fields to the scaffolded query.
     *
     * If the key is omitted a fields is scaffolded from values in the DataObject
     * Otherwise, the key is the name of the field and the value is either:
     * - A string for the description of the auto-scaffolded field
     * - A FieldDefinition object that defines how the field is resolved
     * - An array containing the description and/or casting for the field
     *
     * Ex:
     * [
     *    'MyOtherField' // No description
     *    'MyField' => 'Some description',
     *    'SpecialField' => new FieldDefinition(...),
     *    'ArrayField' => [
     *        'description' => 'Some description',
     *        'casting' => 'Boolean',
     *    ]
     * ]
     *
     * @param  array $fieldData
     * @return $this
     */
    public function addFields(array $fieldData)
    {
        foreach ($fieldData as $k => $data) {
            $assoc = !is_numeric($k);
            $name = $assoc ? $k : $data;

            // If we're given a field definition we can accept that interface
            if ($data instanceof FieldDefinition) {
                $this->addField($name, $data);
                continue;
            }

            $casting = null;

            if (is_array($data)) {
                if (isset($data['casting'])) {
                    $casting = $data['casting'];

                    // Convert to object if Injector has a definition for the given casting
                    if (Injector::inst()->has($casting)) {
                        $casting = Injector::inst()->create($casting);
                    }
                }
                if (isset($data['description'])) {
                    $data = $data['description'];
                }
            }

            $this->scaffoldField($name, $data, $casting);
        }

        return $this;
    }

    /**
     * Add a field definition to this query under the given field name
     *
     * @param string $name
     * @param FieldDefinition $definition
     * @return $this
     */
    public function addField($name, FieldDefinition $definition)
    {
        $this->fields[$name] = $definition;

        return $this;
    }

    /**
     * Scaffold and add a field definition from the dataobject given only a field name that exists on the data object
     * Optionally:
     * - provide a description for the self-documenting GraphQL endpoints.
     * - indicate what type this field should be if the GraphQL field type is different than the field type in the
     *      DataObject
     *
     * @param $name
     * @param null $description
     * @param Type|TypeCreatorExtension|string|null $type
     * @return $this
     */
    public function scaffoldField($name, $description = null, $type = null)
    {
        $instance = $this->getDataObjectInstance();

        // Assert a valid field name
        StaticSchema::inst()->assertValidFieldName($this->getDataObjectInstance(), $name);

        // Get the DBField definition for this field
        $field = $instance->obj($name);

        if ($field instanceof DataObjectInterface) {
            $type = StaticSchema::inst()->typeNameForDataObject(get_class($field));
        }
        elseif (!$field instanceof DBField) {
            return $this;
        }

        // Create and add a FieldDefinition
        $this->addField($name, new FieldDefinition(
            $description,
            $type ?: $field,
            $this->getDefaultResolver()
        ));

        return $this;
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
     * @param array|string $exclusions
     * @param bool $includeHasOne
     *
     * @return $this
     */
    public function addAllFieldsExcept($exclusions, $includeHasOne = false)
    {
        if (!is_array($exclusions)) {
            $exclusions = [$exclusions];
        }
        $fields = $this->allFieldsFromDataObject($includeHasOne);
        $filteredFields = array_diff($fields, $exclusions);

        return $this->addFields($filteredFields);
    }

    /**
     * @param string $field
     * @return $this
     */
    public function removeField($field)
    {
        return $this->removeFields([$field]);
    }

    /**
     * Provide an array of field names to remove from the scaffolder
     *
     * @param array $fields
     * @return $this
     */
    public function removeFields(array $fieldNames)
    {
        foreach ($fieldNames as $field) {
            unset($this->fields[$field]);
        }

        return $this;
    }

    /**
     * @return ArrayList|FieldDefinition[]
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
     * Sets the description to an existing field.
     *
     * @param  string $field
     * @param  string $description
     * @return $this
     * @throws InvalidArgumentException When attempting to set the description of a non existant field
     */
    public function setFieldDescription($field, $description)
    {
        $existing = isset($this->fields[$field]);
        if (!$existing) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot set description of %s. It has not been added to %s.',
                    $field,
                    $this->getDataObjectClass()
                )
            );
        }

        $this->fields[$field]->setDescription($description);

        return $this;
    }

    /**
     * Gets the Description property from a field, given a name
     *
     * @param  string $field
     * @return string
     * @throws Exception
     */
    public function getFieldDescription($field)
    {
        $item = $this->fields[$field];

        if (!$item) {
            throw new Exception(
                sprintf(
                    'Tried to get field description for %s, but it has not been added to %s',
                    $field,
                    $this->getDataObjectClass()
                )
            );
        }

        return $item->getDescription();
    }

    /**
     * Removes an operation.
     *
     * @param  string $identifier
     * @return $this
     */
    public function removeOperation($identifier)
    {
        $this->operations->removeByIdentifier($identifier);

        return $this;
    }

    /**
     * Adds all operations that are registered
     *
     * @return $this
     */
    public function addAllOperations()
    {
        foreach (OperationScaffolder::getOperations() as $id => $operation) {
            $this->operation($id);
        }
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
        $existing = $this->operations->findByIdentifier($operation);

        if ($existing) {
            return $existing;
        }

        $scaffoldClass = OperationScaffolder::getClassFromIdentifier($operation);
        if (!$scaffoldClass) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid operation: %s added to %s',
                    $operation,
                    $this->getDataObjectClass()
                )
            );
        }
        /**
         * @var OperationScaffolder $scaffolder
         */
        $scaffolder = Injector::inst()->createWithArgs($scaffoldClass, [$this->getDataObjectClass()]);

        $this->operations->push(
            $scaffolder->setChainableParent($this)
        );

        return $scaffolder;
    }


    /**
     * Finds or adds a nested query, e.g. has_many/many_many relation, or a query created
     * with a custom scaffolder
     *
     * @param string $fieldName
     * @param QueryScaffolder $queryScaffolder
     * @return OperationScaffolder|ListQueryScaffolder
     */
    public function nestedQuery($fieldName, QueryScaffolder $queryScaffolder = null)
    {
        $query = isset($this->nestedQueries[$fieldName]) ? $this->nestedQueries[$fieldName] : null;

        if ($query) {
            return $query;
        }

        if (!$queryScaffolder) {
            // If no scaffolder if provided, try to infer the type by resolving the field
            $result = $this->getDataObjectInstance()->obj($fieldName);

            if (!$result instanceof DataList && !$result instanceof ArrayList) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s::addNestedQuery() tried to add %s, but must be passed a method name or relation that returns a DataList or ArrayList',
                        __CLASS__,
                        $fieldName
                    )
                );
            }

            $queryScaffolder = new ListQueryScaffolder(
                $fieldName,
                null,
                function ($obj) use ($fieldName) {
                    /* @var DataObject $obj */
                    return $obj->obj($fieldName);
                },
                $result->dataClass()
            );
        }

        $queryScaffolder->setChainableParent($this);
        $queryScaffolder->setNested(true);
        $this->nestedQueries[$fieldName] = $queryScaffolder;

        return $queryScaffolder;
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
     * @deprecated 2.0.0..3.0.0 Use StaticSchema::getAncestry($class) instead
     */
    public function getAncestralClasses()
    {
        Deprecation::notice('3.0', 'Use StaticSchema::getAncestry($class) instead');

        return StaticSchema::inst()->getAncestry($this->getDataObjectClass());
    }

    /**
     * Clones this scaffolder to another class, copying over only valid fields and operations
     * @param DataObjectScaffolder $target
     * @return DataObjectScaffolder
     */
    public function cloneTo(DataObjectScaffolder $target)
    {
        $target->addFields($this->getFields()->toArray());
        foreach ($this->getOperations() as $op) {
            $identifier = OperationScaffolder::getIdentifier($op);
            $target->operation($identifier);
        }

        return $target;
    }

    /**
     * Applies settings from an array, i.e. YAML
     *
     * @param  array $config
     * @return $this
     * @throws Exception
     */
    public function applyConfig(array $config)
    {
        $dataObjectClass = $this->getDataObjectClass();
        if (empty($config['fields'])) {
            throw new Exception(
                "No array of fields defined for $dataObjectClass"
            );
        }
        if (isset($config['fields'])) {
            if ($config['fields'] === SchemaScaffolder::ALL) {
                $this->addAllFields(true);
            } elseif (is_array($config['fields'])) {
                $this->addFields($config['fields']);
            } else {
                throw new Exception(
                    sprintf(
                        "Fields must be an array, or '%s' for all fields in $dataObjectClass",
                        SchemaScaffolder::ALL
                    )
                );
            }
        }

        if (isset($config['excludeFields'])) {
            if (!is_array($config['excludeFields']) || ArrayLib::is_associative($config['excludeFields'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        '"excludeFields" must be an enumerated list of fields. See %s',
                        $this->getDataObjectClass()
                    )
                );
            }

            $this->removeFields($config['excludeFields']);
        }

        if (isset($config['fieldDescriptions'])) {
            if (!ArrayLib::is_associative($config['fieldDescriptions'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        '"fieldDescripions" must be a map of field name to description. See %s',
                        $this->getDataObjectClass()
                    )
                );
            }

            foreach ($config['fieldDescriptions'] as $fieldName => $description) {
                $this->setFieldDescription($fieldName, $description);
            }
        }

        if (isset($config['operations'])) {
            if ($config['operations'] ===  SchemaScaffolder::ALL) {
                $config['operations'] = [];
                foreach (OperationScaffolder::getOperations() as $id => $operation) {
                    $config['operations'][$id] = true;
                }
            }

            if (!ArrayLib::is_associative($config['operations'])) {
                throw new Exception(
                    'Operations field must be a map of operation names to a map of settings, or true/false'
                );
            }

            foreach ($config['operations'] as $opID => $opSettings) {
                if ($opSettings === false) {
                    continue;
                }
                $this->operation($opID)
                    ->applyConfig((array)$opSettings);
            }
        }

        if (isset($config['nestedQueries'])) {
            if (!ArrayLib::is_associative($config['nestedQueries'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        '"nestedQueries" must be a map of relation name to a map of settings, or true/false. See %s',
                        $this->getDataObjectClass()
                    )
                );
            }

            foreach ($config['nestedQueries'] as $relationName => $settings) {
                if ($settings === false) {
                    continue;
                } elseif (is_string($settings)) {
                    if (is_subclass_of($settings, QueryScaffolder::class)) {
                        $queryScaffolder = new $settings($relationName);
                        $this->nestedQuery($relationName, $queryScaffolder);
                    } else {
                        throw new InvalidArgumentException(sprintf(
                            'Tried to specify %s as a custom query scaffolder for %s on %s, but it is not a subclass of %s.',
                            $settings,
                            $relationName,
                            $this->getDataObjectClass(),
                            QueryScaffolder::class
                        ));
                    }
                } else {
                    $this->nestedQuery($relationName)
                        ->applyConfig((array)$settings);
                }
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
        return new ObjectType(
            [
                'name' => $this->getTypeName(),
                'fields' => function () use ($manager) {
                    return $this->createFields($manager);
                },
            ]
        );
    }

    /**
     * Adds the type to the Manager.
     *
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $this->extend('onBeforeAddToManager', $manager);
        $scaffold = $this->scaffold($manager);
        if (!$manager->hasType($this->getTypeName())) {
            $manager->addType($scaffold, $this->getTypeName());
        }

        foreach ($this->operations as $op) {
            $op->addToManager($manager);
        }

        foreach ($this->nestedQueries as $scaffold) {
            $scaffold->addToManager($manager);
        }

        $this->extend('onAfterAddToManager', $manager);
    }

    /**
     * @param bool $includeHasOne
     *
     * @return array
     */
    protected function allFieldsFromDataObject($includeHasOne = false)
    {
        $fields = [];
        $db = DataObject::config()->get('fixed_fields');
        $extra = Config::inst()->get($this->getDataObjectClass(), 'db');
        if ($extra) {
            $db = array_merge($db, $extra);
        }

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
        $fields = array_keys($this->fields->toArray());

        foreach ($fields as $fieldName) {
            $result = $instance->obj($fieldName);
            if ($result instanceof DataObjectInterface) {
                $types[$fieldName] = get_class($result);
            }
        }

        return $types;
    }

    /**
     * Gets the list of class names that are in nested queries
     *
     * @return array
     */
    protected function nestedConnections()
    {
        $queries = [];
        $inst = $this->getDataObjectInstance();
        foreach ($this->nestedQueries as $name => $q) {
            $result = $inst->obj($name);
            if ($result instanceof DataList || $result instanceof UnsavedRelationList) {
                $queries[$name] = $result->dataClass();
            }
        }

        return $queries;
    }

    /**
     * Throw an exception if the data for this field appears to be a list of items. HasMany relations should be defined
     * by nested queries in GraphQL
     *
     * @param $data
     * @param $fieldName
     * @throws InvalidArgumentException
     */
    protected function assertValidFieldData($data, $fieldName)
    {
        if ($data instanceof SS_List) {
            throw new InvalidArgumentException(
                sprintf(
                    'Fieldname %s added to %s returns a list. This should be defined as a nested query using addNestedQuery(%s)',
                    $fieldName,
                    $this->getDataObjectClass(),
                    $fieldName
                )
            );
        }
    }

    /**
     * Validates the raw field map and creates a map suitable for ObjectType
     *
     * @param  Manager $manager
     * @return array
     */
    protected function createFields(Manager $manager)
    {
        $fieldMap = [];

        if (!$this->fields->exists()) {
            $this->addFields(
                Config::inst()->get(self::class, 'default_fields')
            );
        }

        foreach ($this->fields as $fieldName => $definition) {
            $type = $definition->getType();

            if ($type instanceof DBField || DBField::has_extension($type, TypeCreatorExtension::class)) {
                if (!is_object($type)) {
                    $type = Injector::inst()->create($type);
                }
                $type = $type->getGraphQLType($manager);
            } elseif (is_string($type)) {
                $type = $manager->getType($type);
            }

            if (!$type instanceof Type) {
                throw new InvalidArgumentException(sprintf('Could not determine valid type from "%s"', $type));
            }

            $fieldMap[$fieldName] = [
                'type' => $type,
                'resolve' => $definition->getResolver(),
                'description' => $definition->getDescription(),
            ];
        }

        foreach ($this->nestedQueries as $name => $scaffolder) {
            $scaffold = $scaffolder->scaffold($manager);
            $scaffold['name'] = $name;
            $fieldMap[$name] = $scaffold;
        }

        return $fieldMap;
    }

    /**
     * @return \Closure
     */
    protected function getDefaultResolver()
    {
        return function ($obj, $args, $context, $info) {
            /**
             * @var DataObject $obj
             */
            $field = $obj->obj($info->fieldName);
            // return the raw field value, or checks like `is_numeric()` fail
            if ($field instanceof DBField && $field->isInternalGraphQLType()) {
                return $field->getValue();
            }
            return $field;
        };
    }
}
