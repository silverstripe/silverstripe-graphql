<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use League\Flysystem\Exception;
use SilverStripe\Core\Injector\Injector;
use InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\ViewableData;

/**
 * The entry point for a GraphQL scaffolding definition. Holds DataObject type definitions,
 * and their nested Mutation/Query definitions.
 */
class SchemaScaffolder implements ManagerMutatorInterface
{
    const CREATE = 'create';

    const READ = 'read';

    const UPDATE = 'update';

    const DELETE = 'delete';

    const ALL = '*';

    /**
     * @var DataObjectScaffolder[]
     */
    protected $types = [];

    /**
     * @var OperationList
     */
    protected $queries;

    /**
     * @var OperationList
     */
    protected $mutations;

    /**
     * Create from an array, e.g. derived from YAML.
     *
     * @param  array $config
     * @return self
     * @throws InvalidArgumentException
     */
    public static function createFromConfig($config)
    {
        $scaffolder = Injector::inst()->get(self::class);
        if (isset($config['types'])) {
            if (!ArrayLib::is_associative($config['types'])) {
                throw new InvalidArgumentException(
                    '"types" must be a map of class name to settings.'
                );
            }

            foreach ($config['types'] as $dataObjectClass => $settings) {
                $scaffolder->type($dataObjectClass)
                    ->applyConfig($settings);
            }
        }

        $queryMap = [
            'queries' => 'query',
            'mutations' => 'mutation',
        ];

        foreach ($queryMap as $group => $method) {
            if (isset($config[$group])) {
                if (!ArrayLib::is_associative($config[$group])) {
                    throw new InvalidArgumentException(
                        sprintf(
                            '"%s" must be a map of operation name to settings.',
                            $group
                        )
                    );
                }

                foreach ($config[$group] as $fieldName => $fieldSettings) {
                    if (!isset($fieldSettings['type'])) {
                        throw new InvalidArgumentException(
                            sprintf(
                                '"%s" must have a "type" field. See %s',
                                $group,
                                $fieldName
                            )
                        );
                    }

                    $scaffolder->$method($fieldName, $fieldSettings['type'])
                        ->applyConfig($fieldSettings);
                }
            }
        }

        return $scaffolder;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queries = OperationList::create([]);
        $this->mutations = OperationList::create([]);
    }

    /**
     * Finds or makes a DataObject definition.
     *
     * @param  string $class
     * @return DataObjectScaffolder
     * @throws InvalidArgumentException
     */
    public function type($class)
    {
        foreach ($this->types as $scaffold) {
            if ($scaffold->getDataObjectClass() == $class) {
                return $scaffold;
            }
        }

        $scaffold = (new DataObjectScaffolder($class))
                ->setChainableParent($this);
        $this->types[] = $scaffold;

        return $scaffold;
    }

    /**
     * Find or make a query.
     *
     * @param  string                     $name
     * @param  string                     $class
     * @param  callable|ResolverInterface $resolver
     * @return QueryScaffolder
     */
    public function query($name, $class, $resolver = null)
    {
        /**
         * @var QueryScaffolder $query
        */
        $query = $this->queries->findByName($name);
        if ($query) {
            return $query;
        }

        $operationScaffold = (new QueryScaffolder(
            $name,
            ScaffoldingUtil::typeNameForDataObject($class),
            $resolver
        ))->setChainableParent($this);

        $this->queries->push($operationScaffold);

        return $operationScaffold;
    }

    /**
     * Find or make a mutation.
     *
     * @param  string                     $name
     * @param  string                     $class
     * @param  callable|ResolverInterface $resolver
     * @return bool|MutationScaffolder
     */
    public function mutation($name, $class, $resolver = null)
    {
        $mutation = $this->mutations->findByName($name);

        if ($mutation) {
            return $mutation;
        }

        $operationScaffold = (new MutationScaffolder(
            $name,
            ScaffoldingUtil::typeNameForDataObject($class),
            $resolver
        ))->setChainableParent($this);

        $this->mutations->push($operationScaffold);

        return $operationScaffold;
    }

    /**
     * Removes a mutation.
     *
     * @param  string $name
     * @return $this
     */
    public function removeMutation($name)
    {
        $this->mutations->removeByName($name);

        return $this;
    }

    /**
     * Removes a query.
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeQuery($name)
    {
        $this->queries->removeByName($name);

        return $this;
    }

    /**
     * @return DataObjectScaffolder[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Returns true if the type has been added to the scaffolder
     *
     * @param  string $dataObjectClass
     * @return bool
     */
    public function hasType($dataObjectClass)
    {
        foreach ($this->types as $scaffold) {
            if ($scaffold->getDataObjectClass() == $dataObjectClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return OperationList
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @return OperationList
     */
    public function getMutations()
    {
        return $this->mutations;
    }

    /**
     * Adds every DataObject and its dependencies to the Manager.
     *
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        // Register fixed types
        $fixedTypes = Config::inst()->get(self::class, 'fixed_types');
        if ($fixedTypes) {
            if (!is_array($fixedTypes)) {
                throw new Exception(
                    sprintf(
                        '%s.fixed_types must be an array',
                        __CLASS__
                    )
                );
            }
            foreach ($fixedTypes as $className) {
                $instance = Injector::inst()->get($className);
                if (!$instance instanceof ViewableData) {
                    throw new Exception(
                        sprintf(
                            'Cannot auto register class %s. It is not a subclass of %s',
                            $className,
                            ViewableData::class
                        )
                    );
                }
                if (!$instance->hasExtension(TypeCreatorExtension::class)) {
                    throw new Exception(
                        sprintf(
                            'Cannot auto register class %s. Is does not have the extension %s.',
                            $className,
                            TypeCreatorExtension::class
                        )
                    );
                }
                $instance->addToManager($manager);
            }
        }

        foreach ($this->types as $scaffold) {
            // Add dependent classes, e.g has_one, has_many nested queries
            foreach ($scaffold->getDependentClasses() as $class) {
                $this->type($class);
            }
            // The fields and operations explicitly exposed to the lowest type in the ancestry
            $exposedFields = $scaffold->getFields();
            $exposedOperations = $scaffold->getOperations();

            // Expose all the ancestors, and add the exposed fields and operations
            foreach ($scaffold->getAncestralClasses() as $class) {
                $ancestorType = $this->type($class);
                $inst = $ancestorType->getDataObjectInstance();
                foreach ($exposedFields as $field) {
                    if (ScaffoldingUtil::isValidFieldName($inst, $field->Name)) {
                        $ancestorType->addField($field->Name, $field->Description);
                    }
                }
                foreach ($exposedOperations as $op) {
                    if ($op instanceof CRUDInterface) {
                        $ancestorType->operation($op->getIdentifier());
                    }
                }
            }
        }

        // Add all DataObjects to the manager
        foreach ($this->types as $scaffold) {
            $scaffold->addToManager($manager);
        }

        foreach ($this->queries as $scaffold) {
            $scaffold->addToManager($manager);
        }

        foreach ($this->mutations as $scaffold) {
            $scaffold->addToManager($manager);
        }
    }
}
