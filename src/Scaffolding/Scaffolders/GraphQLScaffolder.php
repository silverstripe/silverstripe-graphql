<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\Core\Injector\Injector;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Creators\MutationOperationCreator;
use SilverStripe\GraphQL\Scaffolding\Creators\QueryOperationCreator;

/**
 * The entry point for a GraphQL scaffolding definition. Holds DataObject type definitions,
 * and their nested Mutation/Query definitions.
 *
 * @package SilverStripe\GraphQL\Scaffolding
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
     * Create from an array, e.g. derived from YAML
     *
     * @param $config
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function createFromConfig($config)
    {
        $scaffolder = Injector::inst()->create(GraphQLScaffolder::class);
        foreach ($config as $dataObjectName => $settings) {
            if (empty($settings['fields']) || !is_array($settings['fields'])) {
                throw new \Exception(
                    "No array of fields defined for $dataObjectName"
                );
            }

            $scaffolder->dataObject($dataObjectName)
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
                                    "Invalid operation: $op"
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
                    $scaffolder->dataObject($dataObjectName)
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
                        $scaffolder->dataObject($dataObjectName)
                            ->$method($fieldName)
                            ->setResolver($fieldSettings['resolver'])
                            ->addArgs($args);
                    }
                }
            }
        }

        return $scaffolder;
    }

    /**
     * Finds or makes a DataObject definition
     *
     * @param DataObjectScaffold $scaffold
     * @throws InvalidArgumentException
     */
    public function dataObject($name, $typeName = null)
    {
        foreach ($this->scaffolds as $scaffold) {
            if ($scaffold->getDataObjectName() == $name) {
                return $scaffold;
            }
        }

        $scaffold = new DataObjectScaffolder($name, $typeName);
        $this->scaffolds[] = $scaffold;

        return $scaffold;
    }


    /**
     * Adds every DataObject and its dependencies to the Manager
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        // Gather all new types that were implicitly added through
        // addFields(), e.g. member->addFields(['Groups'])
        $extraDataObjects = [];
        foreach ($this->scaffolds as $scaffold) {
            $types = $scaffold->getExtraDataObjects();
            foreach ($types as $fieldName => $className) {
                $extraDataObjects[] = $className;
            }
        }

        $extraDataObjects = array_unique($extraDataObjects);

        // Ensure that all these types exist in the scaffolder
        foreach ($extraDataObjects as $className) {
            $this->dataObject($className);
        }

        // Add all DataObjects to the manager
        foreach ($this->scaffolds as $scaffold) {
            $scaffold->addToManager($manager);
        }
    }
}