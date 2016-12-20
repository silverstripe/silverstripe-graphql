<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\Core\Injector\Injector;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Creators\MutationOperationCreator;
use SilverStripe\GraphQL\Scaffolding\Creators\QueryOperationCreator;
use SilverStripe\GraphQL\Scaffolding\OperationList;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;

/**
 * The entry point for a GraphQL scaffolding definition. Holds DataObject type definitions,
 * and their nested Mutation/Query definitions.
 */
class GraphQLScaffolder implements ManagerMutatorInterface
{

    const CREATE = 1;

    const READ = 2;

    const UPDATE = 3;

    const DELETE = 4;

    /**
     * @var array
     */
    protected $scaffolds = [];

    /**
     * @var OperationList
     */
    protected $queries;

    /**
     * @var  OperationList
     */
    protected $mutations;
	
    /**
     * Create from an array, e.g. derived from YAML
     *
     * @param $config
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function createFromConfig($config)
    {
        $scaffolder = Injector::inst()->create(GraphQLScaffolder::class);
        foreach ($config as $dataObjectClass => $settings) {
            if (empty($settings['fields']) || !is_array($settings['fields'])) {
                throw new \Exception(
                    "No array of fields defined for $dataObjectClass"
                );
            }

            $scaffolder->type($dataObjectClass)
                ->addFields($settings['fields']);

            if (isset($settings['operations'])) {
                $ops = [];
                if ($settings['operations'] === 'all') {
                    $ops = [self::CREATE, self::READ, self::UPDATE, self::DELETE];
                } else {
                    if (is_array($settings['operations'])) {
                        foreach ($settings['operations'] as $op) {
                            $constStr = sprintf(
                                '%s::%s',
                                GraphQLScaffolder::class,
                                strtoupper($op)
                            );
                            if (defined($constStr)) {
                                $ops[] = constant($constStr);
                            } else {
                                throw new \Exception(
                                    "Invalid operation: $op on $dataobjectClass"
                                );
                            }
                        }
                    } else {
                        throw new \Exception(
                            "Operations field must be an array of operations or 'all'"
                        );
                    }
                }
                foreach ($ops as $op) {
                    $method = ($op === GraphQLScaffolder::READ) ? 'query' : 'mutation';
                    $scaffolder->type($dataObjectClass)
                        ->$method($op);
                }
            }
            $queryMap = [
                'queries' => 'query',
                'mutations' => 'mutation'
            ];

            foreach ($queryMap as $group => $method) {
                if (isset($settings[$group]) && is_array($settings[$group])) {
                    foreach ($settings[$group] as $fieldName => $fieldSettings) {
                        if (empty($fieldSettings['resolver'])) {
                            throw new \Exception(
                                "All queries and mutations must have a 'resolver' attribute"
                            );
                        }
                        $args = isset($fieldSettings['args']) ? (array)$fieldSettings['args'] : [];
                        $operation = $scaffolder->type($dataObjectClass)
                            ->$method($fieldName)
                            ->setResolver($fieldSettings['resolver'])
                            ->addArgs($args);
                        if($group === 'queries' && isset($fieldSettings['paginate'])) {
                        	$operation->setUsePagination((boolean) $fieldSettings['paginate']);
                        }
                    }
                }
            }
        }

        return $scaffolder;
    }

    /**
     * Constructor
     */
	public function __construct()
	{
        $this->queries = OperationList::create([]);
        $this->mutations = OperationList::create([]);		
	}    

    /**
     * Finds or makes a DataObject definition
     *
     * @param DataObjectScaffold $scaffold
     * @throws InvalidArgumentException
     */
    public function type($class, $typeName = null)
    {
        foreach ($this->scaffolds as $scaffold) {
            if ($scaffold->getDataObjectClass() == $class) {
                return $scaffold;
            }
        }

        $scaffold = new DataObjectScaffolder($class, $typeName);
        $this->scaffolds[] = $scaffold;

        return $scaffold;
    }

    /**
     * Find or make a query
     * @param string $name
     * @param  string $class
     * @param null $resolver
     * @return bool|QueryScaffolder
     */
    public function query($name, $class, $resolver = null)
    {
        $query = $this->queries->findByName($name);

        if ($query) {
            return $query;
        }

        $operationScaffold = new QueryScaffolder(
            $name,
            ScaffoldingUtil::typeNameForDataObject($class),
            $resolver
        );

        $this->queries->push($operationScaffold);

        return $operationScaffold;
    }


    /**
     * Find or make a mutation
     * @param  string $name
     * @param  string  $class
     * @param null $resolver
     * @return bool|MutationScaffolder
     */
    public function mutation($name, $class, $resolver = null)
    {
        $mutation = $this->mutations->findByName($name);

        if ($mutation) {
            return $mutation;
        }

        $operationScaffold = new MutationScaffolder(
            $name,
            ScaffoldingUtil::typeNameForDataObject($class),
            $resolver
        );

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
     * Adds every DataObject and its dependencies to the Manager
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        // Gather all new types that were implicitly added through
        // addFields() and addNestedQuery()
        $types = [];
        foreach ($this->scaffolds as $scaffold) {
            $types = array_merge($types, $scaffold->getDependentTypes());
        }

        $types = array_unique($types);

        // Ensure that all these types exist in the scaffolder
        foreach ($types as $className) {
            $this->type($className);
        }

        // Add all DataObjects to the manager
        foreach ($this->scaffolds as $scaffold) {
            $scaffold->addToManager($manager);
        }
    }
}