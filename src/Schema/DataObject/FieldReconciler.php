<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;

trait FieldReconciler
{
    /**
     * @param array $config
     * @param ModelType $modelType
     * @return array
     * @throws SchemaBuilderException
     */
    private function reconcileFields(
        array $config,
        ModelType $modelType
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
            $fields = array_keys($modelType->getFields());
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
