<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class FieldAccessor
{
    use Injectable;
    use Configurable;

    /**
     * @var callable
     * @config
     */
    private static $normaliser = 'strtolower';

    /**
     * @var array
     */
    private $lookup = [];

    /**
     * @var array
     */
    private static $__mappingCache = [];

    /**
     * @param DataObject $dataObject
     * @return array
     */
    private function getCaseInsensitiveMapping(DataObject $dataObject): array
    {
        $cacheKey = get_class($dataObject);
        $cached = self::$__mappingCache[$cacheKey] ?? null;
        if (!$cached) {
            $schema = $dataObject->getSchema();
            $db = $schema->fieldSpecs(get_class($dataObject));
            $normalFields = array_keys($db);
            $lowercaseFields = array_map($this->config()->get('normaliser'), $normalFields);
            $lookup = array_combine($lowercaseFields, $normalFields);
            self::$__mappingCache[$cacheKey] = $lookup;
        }
        return self::$__mappingCache[$cacheKey];
    }

    /**
     * @param DataObject $dataObject
     * @param string $field
     * @return string|null
     */
    public function normaliseField(DataObject $dataObject, string $field): ?string
    {
        if ($dataObject->hasField($field)) {
            return $field;
        }
        $lookup = $this->getCaseInsensitiveMapping($dataObject);

        $normalised = call_user_func_array($this->config()->get('normaliser'), [$field]);
        return $lookup[$normalised] ?? null;
    }

    /**
     * @param DataObject $dataObject
     * @param string $field
     * @return bool
     */
    public function hasField(DataObject $dataObject, string $field): bool
    {
        return $this->normaliseField($dataObject, $field) !== null;
    }

    /**
     * @param DataObject $dataObject
     * @param string $field
     * @return DBField|null
     */
    public function accessField(DataObject $dataObject, string $field): ?DBField
    {
        $fieldName = $this->normaliseField($dataObject, $field);
        if (!$fieldName) {
            return null;
        }

        return $dataObject->obj($fieldName);
    }

    /**
     * @param DataObject $dataObject
     * @return array
     */
    public function getAllFields(DataObject $dataObject): array
    {
        return array_map(
            $this->config()->get('normaliser'),
            array_keys($this->getCaseInsensitiveMapping($dataObject))
        );
    }
}
