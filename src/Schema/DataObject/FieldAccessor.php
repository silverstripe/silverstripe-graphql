<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Config_ForClass;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\SS_List;
use LogicException;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * A utility class that handles an assortment of issues related to field access on DataObjects,
 * particularly with case insensitivity.
 *
 * It can get all fields on DataObjects, parse dot syntax to traverse relationships, and
 * format fields to their desired casing.
 */
class FieldAccessor
{
    use Injectable;
    use Configurable;

    /**
     * @var array
     * @config
     */
    private static $allowed_aggregates = ['min', 'max', 'avg', 'count', 'sum'];

    /**
     * A function that makes an object property a field name
     * @var callable
     * @config
     */
    private static $field_formatter = [Convert::class, 'upperCamelToLowerCamel'];

    /**
     * @var array
     */
    private $lookup = [];

    /**
     * @var array
     */
    private static $__mappingCache = [];

    /**
     * Get the field as it is defined on the DataObject for case-sensitive access
     * @param DataObject $dataObject
     * @param string $field
     * @return string|null
     */
    public function normaliseField(DataObject $dataObject, string $field): ?string
    {
        $schema = $dataObject->getSchema();
        $class = get_class($dataObject);

        if ($schema->fieldSpec($class, $field) || $schema->unaryComponent($class, $field)) {
            return $field;
        }
        $lookup = $this->getCaseInsensitiveMapping($dataObject);

        $normalised = strtolower($field ?? '');
        $property = $lookup[$normalised] ?? null;
        if ($property) {
            return $property;
        }

        // Sometimes, getters and DB fields overlap, e.g. "getTitle", so this check comes last to ensure
        // the native field gets priority.
        if ($dataObject->hasMethod('get' . $field)) {
            return $field;
        }

        if ($dataObject->hasMethod($field)) {
            return $field;
        }

        if ($dataObject->hasField($field)) {
            return $field;
        }

        return null;
    }

    /**
     * @param DataObject $dataObject
     * @param string $field
     * @return bool
     */
    public function hasField(DataObject $dataObject, string $field): bool
    {
        $path = explode('.', $field ?? '');
        $fieldName = array_shift($path);
        return $this->normaliseField($dataObject, $fieldName) !== null;
    }

    /**
     * Returns true if the field is part of the ORM data structure
     * @param DataObject $dataObject
     * @param string $field
     * @param bool $includeUnary
     * @param bool $includeList
     * @return bool
     */
    public function hasNativeField(
        DataObject $dataObject,
        string $field,
        bool $includeUnary = true,
        bool $includeList = true
    ): bool {
        $schema = DataObject::getSchema();
        $class = get_class($dataObject);
        $normalised = $this->normaliseField($dataObject, $field);
        if (!$normalised) {
            return false;
        }
        if ($schema->databaseField($class, $normalised)) {
            return true;
        }
        if ($includeUnary && $schema->unaryComponent($class, $normalised)) {
            return true;
        }
        if (!$includeList) {
            return false;
        }

        return (
            $schema->manyManyComponent($class, $normalised) ||
            $schema->hasManyComponent($class, $normalised)
        );
    }

    /**
     * Resolves complex dot syntax references.
     *
     * Image.URL (String)
     * FeaturedProduct.Categories.Title ([String] ->column('Title'))
     * FeaturedProduct.Categories.Count() (Int)
     * FeaturedProduct.Categories.Products.Max(Price)
     * Category.Products.Reviews ([Review])
     *
     * @param DataObject $dataObject
     * @param string $field
     * @return DBField|SS_List|DataObject|null
     */
    public function accessField(DataObject $dataObject, string $field)
    {
        if ($path = explode('.', $field ?? '')) {
            if (count($path ?? []) === 1) {
                $fieldName = $this->normaliseField($dataObject, $path[0]);
                if (!$fieldName) {
                    return null;
                }

                return $dataObject->obj($fieldName);
            }
        }

        return $this->parsePath($dataObject, $path);
    }

    /**
     * @param string $field
     * @return string
     */
    public static function formatField(string $field): string
    {
        return call_user_func_array(static::config()->get('field_formatter'), [$field]);
    }

    /**
     * @param DataObject $dataObject
     * @param bool $includeRelations
     * @param bool $includeInherited
     * @return array
     */
    public function getAllFields(DataObject $dataObject, $includeRelations = true, $includeInherited = true): array
    {
        return array_map(
            $this->config()->get('field_formatter'),
            array_values($this->getCaseInsensitiveMapping($dataObject, $includeRelations, $includeInherited) ?? [])
        );
    }

    /**
     * @param DataObject $dataObject
     * @param bool $includeRelations
     * @param bool $includeInherited
     * @return array
     */
    private function getAccessibleFields(
        DataObject $dataObject,
        $includeRelations = true,
        $includeInherited = true
    ): array {
        $class = get_class($dataObject);
        $schema = $dataObject->getSchema();
        $configFlag = $includeInherited ? 0 : Config::UNINHERITED;
        $schemaFlag = $includeInherited ? 0 : DataObjectSchema::UNINHERITED;
        $db = array_keys($schema->fieldSpecs(get_class($dataObject), $schemaFlag) ?? []);
        if (!$includeRelations) {
            return $db;
        }
        /* @var Config_ForClass $config */
        $config = $class::config();
        $hasOnes = array_keys((array) $config->get('has_one', $configFlag));
        $belongsTo = array_keys((array) $config->get('belongs_to', $configFlag));
        $hasMany = array_keys((array) $config->get('has_many', $configFlag));
        $manyMany = array_keys((array) $config->get('many_many', $configFlag));
        $belongsManyMany = array_keys((array) $config->get('belongs_many_many', $configFlag));

        return array_merge($db, $hasOnes, $belongsTo, $hasMany, $manyMany, $belongsManyMany);
    }

    /**
     * @param DataObject $dataObject
     * @param bool $includeRelations
     * @param bool $includeInherirted
     * @return array
     */
    private function getCaseInsensitiveMapping(
        DataObject $dataObject,
        $includeRelations = true,
        $includeInherirted = true
    ): array {
        $cacheKey = md5(json_encode([
            get_class($dataObject),
            ($includeRelations ? '_relations' : ''),
            ($includeInherirted ? '_inherited' : '')
        ]) ?? '');
        $cached = self::$__mappingCache[$cacheKey] ?? null;
        if (!$cached) {
            $normalFields = $this->getAccessibleFields($dataObject, $includeRelations, $includeInherirted);
            $lowercaseFields = array_map('strtolower', $normalFields ?? []);
            $lookup = array_combine($lowercaseFields ?? [], $normalFields ?? []);
            self::$__mappingCache[$cacheKey] = $lookup;
        }
        return self::$__mappingCache[$cacheKey];
    }

    /**
     *
     * @param DataObject|DataList|DBField $subject
     * @param array $path
     * @return string|int|bool|array|DataList
     * @throws LogicException
     */
    private function parsePath($subject, array $path)
    {
        $nextField = array_shift($path);
        if ($subject instanceof DataObject) {
            $result = $subject->obj($nextField);
            if (!is_object($result)) {
                return $result;
            }
            if ($result instanceof DBField) {
                return $result->getValue();
            }
            return $this->parsePath($result, $path);
        }

        if ($subject instanceof DataList || $subject instanceof UnsavedRelationList) {
            if (!$nextField) {
                return $subject;
            }

            // Aggregate field, eg. Comments.Count(), Page.FeaturedProducts.Avg(Price)
            if (preg_match('/([A-Za-z]+)\(\s*(?:([A-Za-z_*][A-Za-z0-9_]*))?\s*\)$/', $nextField ?? '', $matches)) {
                $aggregateFunction = strtolower($matches[1] ?? '');
                $aggregateColumn = $matches[2] ?? null;
                if (!in_array($aggregateFunction, $this->config()->get('allowed_aggregates') ?? [])) {
                    throw new LogicException(sprintf(
                        'Cannot call aggregate function %s',
                        $aggregateFunction
                    ));
                }
                if (method_exists($subject, $aggregateFunction ?? '')) {
                    return call_user_func_array([$subject, $aggregateFunction], [$aggregateColumn]);
                }

                return null;
            }

            $singleton = DataObject::singleton($subject->dataClass());
            if ($singleton->hasField($nextField)) {
                return $subject->column($nextField);
            }

            $maybeList = $singleton->obj($nextField);
            if ($maybeList instanceof RelationList || $maybeList instanceof UnsavedRelationList) {
                return $this->parsePath($subject->relation($nextField), $path);
            }
        }

        throw new LogicException(sprintf(
            'Cannot resolve field %s on list of class %s',
            $nextField,
            $subject->dataClass()
        ));
    }
}
