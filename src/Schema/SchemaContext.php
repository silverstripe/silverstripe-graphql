<?php


namespace SilverStripe\GraphQL\Schema;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Config\AbstractConfiguration;
use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Schema\DataObject\ModelCreator;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolver;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;

/**
 * Encapsulates configuration required for a {@link Schema} object.
 */
class SchemaContext extends AbstractConfiguration
{
    /**
     * @var array
     */
    private $__modelCache = [];

    /**
     * @param string $modelName
     * @return ModelConfiguration
     * @throws SchemaBuilderException
     */
    public function getModelConfiguration(string $modelName): ModelConfiguration
    {
        $config = $this->get(['modelConfig', $modelName], []);

        return new ModelConfiguration($config);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getResolvers(): array
    {
        return $this->get('resolvers', []);
    }

    /**
     * @param string $class
     * @return SchemaModelInterface|null
     * @throws SchemaBuilderException
     */
    public function createModel(string $class): ?SchemaModelInterface
    {
        $cached = $this->__modelCache[$class] ?? null;
        if ($cached) {
            return $cached;
        }
        /* @var ModelCreator $creator */
        foreach ($this->get('modelCreators', []) as $creatorClass) {
            $creator = Injector::inst()->create($creatorClass);
            Schema::invariant(
                $creator instanceof ModelCreator,
                'Class %s is not an instance of %s',
                $creatorClass,
                ModelCreator::class
            );
            if ($creator->appliesTo($class)) {
                $model = $creator->createModel($class, $this);
                $this->__modelCache[$class] = $model;

                return $model;
            }
        }

        return null;
    }

    /**
     * @param string|null $typeName
     * @param Field|null $field
     * @return ResolverReference
     * @throws SchemaBuilderException
     */
    public function discoverResolver(?string $typeName = null, ?Field $field = null): ResolverReference
    {
        $strategy = $this->get('resolverStrategy', [DefaultResolverStrategy::class, 'getResolverMethod']);
        Schema::invariant(
            is_callable($strategy),
            'SchemaContext resolverStrategy must be callable'
        );

        $callable = call_user_func_array($strategy, [$this->getResolvers(), $typeName, $field]);
        if ($callable) {
            return ResolverReference::create($callable);
        }

        return ResolverReference::create(
            $this->get('defaultResolver', [DefaultResolver::class, 'defaultFieldResolver'])
        );
    }

    /**
     * @return callable
     * @throws SchemaBuilderException
     */
    public function getPluraliser(): callable
    {
        return $this->get('pluraliser', [static::class, 'pluralise']);
    }

    /**
     * @param string $typeName
     * @return string
     */
    public static function pluralise(string $typeName): string
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }

    /**
     * @param array $typeMapping
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setTypeMapping(array $typeMapping): self
    {
        return $this->set('typeMapping', $typeMapping);
    }

    /**
     * @param array $fields
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setFieldMapping(array $fields): self
    {
        return $this->set('fieldMapping', $fields);
    }


    /**
     * @param string $class
     * @return string|null
     * @throws SchemaBuilderException
     */
    public function getTypeNameForClass(string $class): ?string
    {
        return $this->get(['typeMapping', $class]);
    }

    /**
     * @param string $typeName
     * @param string $fieldName
     * @return array|null
     * @throws SchemaBuilderException
     */
    public function mapField(string $typeName, string $fieldName): ?array
    {
        return $this->get(['fieldMapping', $typeName, $fieldName]);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return array|null
     * @throws SchemaBuilderException
     */
    public function mapFieldByClassName(string $className, string $fieldName): ?array
    {
        $typeName = $this->getTypeNameForClass($className);
        if (!$typeName) {
            return null;
        }

        return $this->mapField($typeName, $fieldName);
    }
    /**
     * @param string $rootType
     * @param string $path
     * @return string|null
     * @throws SchemaBuilderException
     */
    public function mapPath(string $rootType, string $path): ?string
    {
        $map = [];
        $currentType = $rootType;
        foreach (explode('.', $path) as $fieldName) {
            $info = $this->mapField($currentType, $fieldName);
            if (!$info) {
                return null;
            }
            list($typeName, $prop) = $info;
            $map[] = $prop;
            $currentType = $typeName;
        }

        return implode('.', $map);
    }
}
