<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;

trait FieldReconciler
{
    /**
     * @param array $config
     * @param DataObject $dataObject
     * @param FieldAccessor $fieldAccessor
     * @return array
     * @throws SchemaBuilderException
     */
    private function reconcileFields(
        array $config,
        DataObject $dataObject,
        FieldAccessor $fieldAccessor
    ): array {
        $configFields = $config['fields'] ?? null;
        $fields = [];
        if ($configFields) {
            Schema::assertValidConfig($configFields);
            foreach ($configFields as $fieldName => $bool) {
                if ($bool === false) {
                    continue;
                }
                $fields[] = $fieldName;
            }
        } else {
            $fields = $fieldAccessor->getAllFields($dataObject, false);
        }
        $configExclude = $config['exclude'] ?? null;
        $excluded = [];
        if ($configExclude) {
            Schema::assertValidConfig($configExclude);
            foreach ($configExclude as $fieldName => $bool) {
                if ($bool === false) {
                    continue;
                }
                $excluded[] = $fieldName;
            }
            $includedFields = array_diff($fields, $excluded);
        } else {
            $includedFields = $fields;
        }

        return $includedFields;
    }
}
