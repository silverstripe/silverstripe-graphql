<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\View\ViewableData;

/**
 * The entry point for a GraphQL scaffolding definition. Holds DataObject type definitions,
 * and their nested Mutation/Query definitions.
 */
class SchemaScaffolder implements ManagerMutatorInterface
{
    use Extensible;

    const ALL = '*';

    const CREATE = 'create';

    const READ = 'read';

    const UPDATE = 'update';

    const DELETE = 'delete';

    const READ_ONE = 'readOne';

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
        // Remove leading backslash. All namespaces are assumed absolute in YAML
        $class = ltrim($class, '\\');

        foreach ($this->types as $scaffold) {
            if ($scaffold->getDataObjectClass() === $class) {
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
     * @param  callable|OperationResolver $resolver
     * @return QueryScaffolder|ListQueryScaffolder
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

        $operationScaffold = (new ListQueryScaffolder($name, null, $resolver, $class))
            ->setChainableParent($this);

        $this->queries->push($operationScaffold);

        return $operationScaffold;
    }

    /**
     * Find or make a mutation.
     *
     * @param  string                     $name
     * @param  string                     $class
     * @param  callable|OperationResolver $resolver
     * @return bool|MutationScaffolder
     */
    public function mutation($name, $class, $resolver = null)
    {
        $mutation = $this->mutations->findByName($name);

        if ($mutation) {
            return $mutation;
        }

        $operationScaffold = (new MutationScaffolder($name, null, $resolver, $class))
            ->setChainableParent($this);

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
     * Gets all nested queries for all types
     * @return array
     */
    public function getNestedQueries()
    {
        $queries = [];
        foreach ($this->types as $scaffold) {
            $queries = array_merge($queries, $scaffold->getNestedQueries());
        }

        return $queries;
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
        $this->registerFixedTypes($manager);
        $this->registerPeripheralTypes($manager);

        $this->extend('onBeforeAddToManager', $manager);

        // Add all DataObjects to the manager
        foreach ($this->types as $scaffold) {
            $scaffold->addToManager($manager);
            $inheritanceScaffolder = new InheritanceScaffolder(
                $scaffold->getDataObjectClass(),
                StaticSchema::config()->get('inheritanceTypeSuffix')
            );
            // Due to shared ancestry, it's inevitable that the same union type will get added multiple times.
            if (!$manager->hasType($inheritanceScaffolder->getName())) {
                $inheritanceScaffolder->addToManager($manager);
            }
        }

        foreach ($this->queries as $scaffold) {
            $scaffold->addToManager($manager);
        }

        foreach ($this->mutations as $scaffold) {
            $scaffold->addToManager($manager);
        }

        $this->extend('onAfterAddToManager', $manager);
    }

    /**
     * Registers special SS types that are made available to all schemas, e.g. DBFile ObjectType
     *
     * @param Manager $manager
     * @throws Exception
     */
    protected function registerFixedTypes(Manager $manager)
    {
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
    }

    /**
     * Registers types and respective operations for all ancestors of exposed dataobjects
     * @param Manager $manager
     */
    protected function registerPeripheralTypes(Manager $manager)
    {
        $schema = StaticSchema::inst();
        foreach ($this->types as $scaffold) {
            // Add dependent classes, e.g has_one, has_many nested queries
            foreach ($scaffold->getDependentClasses() as $class) {
                $this->type($class);
                // Implicitly, all subclasses are added (albeit with no fields)
                foreach ($schema->getDescendants($class) as $subclass) {
                    $this->type($subclass);
                }
            }

            $tree = array_merge(
                $schema->getAncestry($scaffold->getDataObjectClass()),
                $schema->getDescendants($scaffold->getDataObjectClass())
            );

            // Expose all the classes along the inheritance chain
            foreach ($tree as $class) {
                $newType = $this->type($class);
                $scaffold->cloneTo($newType);
            }
        }
    }
}
