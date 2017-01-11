<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\Core\Injector\Injector;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\ORM\ArrayLib;

/**
 * The entry point for a GraphQL scaffolding definition. Holds DataObject type definitions,
 * and their nested Mutation/Query definitions.
 */
class GraphQLScaffolder implements ManagerMutatorInterface
{
    const CREATE = 'create';

    const READ = 'read';

    const UPDATE = 'update';

    const DELETE = 'delete';

    /**
     * @var array
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
     * @param $config
     *
     * @return mixed
     *
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
                $scaffold = $scaffolder->type($dataObjectClass)
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
                    throw new InvalidArgumentException(sprintf(
                        '"%s" must be a map of operation name to settings.',
                        $group
                    ));
                }

                foreach ($config[$group] as $fieldName => $fieldSettings) {
                    if (!isset($fieldSettings['type'])) {
                        throw new InvalidArgumentException(sprintf(
                            '"%s" must have a "type" field. See %s',
                            $group,
                            $queryName
                        ));
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
     * @param DataObjectScaffold $scaffold
     *
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
     * @param string $name
     * @param string $class
     * @param null   $resolver
     *
     * @return bool|QueryScaffolder
     */
    public function query($name, $class, $resolver = null)
    {
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
     * @param string $name
     * @param string $class
     * @param null   $resolver
     *
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
     * @param $name
     *
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
     * @param $name
     *
     * @return $this
     */
    public function removeQuery($name)
    {
        $this->queries->removeByName($name);

        return $this;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
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
                    if (ScaffoldingUtil::isValidFieldName($inst, $field)) {
                        $ancestorType->addField($field);
                    }
                }
                foreach ($exposedOperations as $op) {
                    $ancestorType->operation($op->getIdentifier());
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
