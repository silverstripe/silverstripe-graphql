<?php


namespace SilverStripe\GraphQL\Schema;

use SilverStripe\Core\Injector\Injectable;

class SchemaModelCreatorRegistry
{
    use Injectable;

    /**
     * @var SchemaModelCreatorInterface[]
     */
    private $modelCreators = [];

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
     * @param SchemaModelCreatorInterface $creator
     * @return $this
     */
    public function removeModelCreator(SchemaModelCreatorInterface $creator): self
    {
        $class = get_class($creator);
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
        foreach ($this->modelCreators as $creator) {
            if ($creator->appliesTo($class)) {
                return $creator->createModel($class);
            }
        }

        return null;
    }
}
