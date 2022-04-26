<?php


namespace SilverStripe\GraphQL\Schema;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Config\Configuration;
use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Schema\DataObject\ModelCreator;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelCreatorInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolver;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * Encapsulates configuration required for a {@link Schema} object.
 * This should include only that state which might be relevant to
 * query-time execution, such as resolver code.
 */
class SchemaConfig extends Configuration
{
    private array $__modelCache = [];

    /**
     * @throws SchemaBuilderException
     */
    public function getModelConfiguration(string $modelName): ModelConfiguration
    {
        $config = $this->get(['modelConfig', $modelName], []);
        return new ModelConfiguration($config);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function getResolvers(): array
    {
        return $this->get('resolvers', []);
    }


    /**
     * @throws SchemaBuilderException
     */
    public function createModel(string $class): ?SchemaModelInterface
    {
        $cached = $this->__modelCache[$class] ?? null;
        if ($cached) {
            return $cached;
        }
        $creator = $this->getModelCreatorForClass($class);
        if (!$creator) {
            return null;
        }

        $model = $creator->createModel($class, $this);
        $this->__modelCache[$class] = $model;

        return $model;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function discoverResolver(?Type $type = null, ?Field $field = null): ResolverReference
    {
        $strategy = $this->get('resolverStrategy', [DefaultResolverStrategy::class, 'getResolverMethod']);
        Schema::invariant(
            is_callable($strategy),
            'SchemaConfig resolverStrategy must be callable'
        );
        $typeName = $type ? $type->getName() : null;
        $callable = call_user_func_array($strategy, [$this->getResolvers(), $typeName, $field]);
        if ($callable) {
            return ResolverReference::create($callable);
        } elseif ($type && $type->getFieldResolver()) {
            // If no resolver can be discovered, check if the type has a fallback resolver configured.
            return $type->getFieldResolver();
        }

        return ResolverReference::create($this->getDefaultResolver());
    }

    /**
     * @throws SchemaBuilderException
     */
    public function getDefaultResolver(): callable
    {
        return $this->get('defaultResolver', [DefaultResolver::class, 'defaultFieldResolver']);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function getPluraliser(): callable
    {
        return $this->get('pluraliser', [static::class, 'pluralise']);
    }

    public static function pluralise(string $typeName): string
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName ?? '')) {
            $typeName = substr($typeName ?? '', 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }

    /**
     * @param string[] $typeMapping
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setTypeMapping(array $typeMapping): self
    {
        return $this->set('typeMapping', $typeMapping);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function getTypeMapping(): array
    {
        return $this->get('typeMapping', []);
    }

    /**
     * @param string[] $fields
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setFieldMapping(array $fields): self
    {
        return $this->set('fieldMapping', $fields);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function hasModel(string $class): bool
    {
        return (bool) $this->get(['typeMapping', $class]);
    }


    /**
     * @throws SchemaBuilderException
     */
    public function getTypeNameForClass(string $class): ?string
    {
        $name = $this->get(['typeMapping', $class]);
        if ($name) {
            return $name;
        }
        $creator = $this->getModelCreatorForClass($class);
        if (!$creator) {
            return null;
        }
        $model = $creator->createModel($class, $this);
        if ($model) {
            return $model->getTypeName();
        }

        return null;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function mapField(string $typeName, string $fieldName): ?array
    {
        return $this->get(['fieldMapping', $typeName, $fieldName]);
    }

    /**
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
     * @throws SchemaBuilderException
     */
    public function mapPath(string $rootType, string $path): ?string
    {
        $map = [];
        $currentType = $rootType;
        foreach (explode('.', $path ?? '') as $fieldName) {
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

    /**
     * @throws SchemaBuilderException
     */
    private function getModelCreatorForClass(string $class): ?ModelCreator
    {
        /* @var ModelCreator $creator */
        foreach ($this->get('modelCreators', []) as $creatorClass) {
            $creator = Injector::inst()->create($creatorClass);
            Schema::invariant(
                $creator instanceof SchemaModelCreatorInterface,
                'Class %s is not an instance of %s',
                $creatorClass,
                SchemaModelCreatorInterface::class
            );
            if ($creator->appliesTo($class)) {
                return $creator;
            }
        }

        return null;
    }
}
