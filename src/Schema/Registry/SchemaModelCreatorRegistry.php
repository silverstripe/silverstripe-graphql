<?php


namespace SilverStripe\GraphQL\Schema\Registry;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Interfaces\ModelConfigurationProvider;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelCreatorInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

/**
 * A central place for all the classes that create models given a class name
 */
class SchemaModelCreatorRegistry
{
    use Injectable;

    /**
     * @var SchemaModelCreatorInterface[]
     */
    private $modelCreators = [];

    /**
     * @var array
     */
    private $configurations = [];

    /**
     * @var array
     */
    private static $__cache = [];

    /**
     * SchemaModelCreatorRegistry constructor.
     * @param array $modelCreators
     */
    public function __construct(...$modelCreators)
    {
        foreach ($modelCreators as $creator) {
            $this->addModelCreator($creator);
        }
    }

    /**
     * @param SchemaModelCreatorInterface $creator
     * @return $this
     */
    public function addModelCreator(SchemaModelCreatorInterface $creator): self
    {
        $this->modelCreators[] = $creator;

        return $this;
    }

    /**
     * @param SchemaModelCreatorInterface $modelCreator
     * @return $this
     */
    public function removeModelCreator(SchemaModelCreatorInterface $modelCreator): self
    {
        $class = get_class($modelCreator);
        $this->modelCreators = array_filter($this->modelCreators, function ($creator) use ($class) {
            return !$creator instanceof $class;
        });

        return $this;
    }

    /**
     * @param string $class
     * @return SchemaModelInterface|null
     */
    public function getModel(string $class): ?SchemaModelInterface
    {
        $cached = static::$__cache[$class] ?? null;
        if ($cached) {
            return $cached;
        }
        foreach ($this->modelCreators as $creator) {
            if ($creator->appliesTo($class)) {
                $model = $creator->createModel($class);
                if ($model && $model instanceof ModelConfigurationProvider) {
                    $id = $model::getIdentifier();
                    $config = $this->configurations[$id] ?? [];
                    $model->applyModelConfig($config);
                }
                static::$__cache[$class] = $model;

                return $model;
            }
        }

        return null;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfigurations(array $config)
    {
        $configurations = [];
        foreach ($config as $id => $data) {
            $configurations[$id] = ModelConfiguration::create($data);
        }
        $this->configurations = $configurations;

        return $this;
    }

    /**
     * @param string $identifier
     * @return ModelConfiguration|null
     */
    public function getModelConfiguration(string $identifier): ?ModelConfiguration
    {
        return $this->configurations[$identifier] ?? null;
    }
}
