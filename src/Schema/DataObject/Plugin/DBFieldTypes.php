<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\ORM\FieldType\DBEnum;

class DBFieldTypes implements ModelTypePlugin
{
    const IDENTIFIER = 'dbFieldTypes';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param ModelType $type
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        $ignore = $config['ignore'] ?? [];
        $mapping = $config['enumTypeMapping'] ?? [];
        foreach ($type->getFields() as $field) {
            if ($field instanceof ModelField && $field->getModel() instanceof DataObjectModel) {
                $ignored = $ignore[$field->getName()] ?? null;
                if (!!$ignored) {
                    continue;
                }
                $dataClass = $field->getMetadata()->get('dataClass');
                if (!$dataClass) {
                    continue;
                }
                if ($dataClass === DBEnum::class || is_subclass_of($dataClass, DBEnum::class)) {
                    $customName = $mapping[$type->getName()][$field->getName()] ?? null;
                    $this->applyEnum($type, $field, $schema, $customName);
                } elseif ($dataClass === DBComposite::class || is_subclass_of($dataClass, DBComposite::class)) {
                    $this->applyComposite($field, $schema);
                }
            }
        }
    }

    /**
     * @throws SchemaBuilderException
     */
    private function applyEnum(
        ModelType $type,
        ModelField $field,
        Schema $schema,
        ?string $customName = null
    ): void {
        $sng = Injector::inst()->get($field->getModel()->getSourceClass());
        /* @var DBEnum $enumField */
        $enumField = $sng->dbObject($field->getPropertyName());
        if (!$enumField) {
            return;
        }

        $values = $enumField->enumValues();

        // If another enum exists with the same values, recycle it.
        $hash = md5(json_encode($values) ?? '');
        $enum = null;
        foreach ($schema->getEnums() as $candidate) {
            $candidateHash = md5(json_encode($candidate->getValues()) ?? '');
            if ($candidateHash === $hash) {
                $enum = $candidate;
                break;
            }
        }

        if (!$enum) {
            $enum = Enum::create(
                $customName ?: sprintf('%sEnum', $field->getName()),
                $values
            );

            // Name collision detection. If already exists, prefix with type.
            $enums = $schema->getEnums();
            $existing = $enums[$enum->getName()] ?? null;
            if ($existing) {
                $enum->setName($type->getName() . $enum->getName());
            }

            $schema->addEnum($enum);
        }

        $field->setType($enum->getName());
    }

    /**
     * @throws SchemaBuilderException
     */
    private function applyComposite(ModelField $field, Schema $schema): void
    {
        $sng = Injector::inst()->get($field->getModel()->getSourceClass());
        /* @var DBComposite $compositeField */
        $compositeField = $sng->dbObject($field->getPropertyName());
        if ($compositeField) {
            $name = ClassInfo::shortName(get_class($compositeField)) . 'Composite';
            if (!$schema->getType($name)) {
                $nestedDBFields = $compositeField->compositeDatabaseFields();
                $compositeType = Type::create($name);
                foreach ($nestedDBFields as $nestedFieldName => $nestedFieldType) {
                    $graphqlType = Injector::inst()->get($nestedFieldType)->config()->get('graphql_type');
                    $fieldName = FieldAccessor::formatField($nestedFieldName);
                    $compositeType->addField($fieldName, $graphqlType);
                }
                $schema->addType($compositeType);
            }

            $field->setType($name);
            $field->addResolverAfterware([static::class, 'resolveComposite']);
        }
    }

    /**
     * @param mixed @obj
     * @param array $args
     * @return mixed
     */
    public static function resolveComposite($obj, array $args)
    {
        if ($obj instanceof DBComposite) {
            $result = [];
            foreach ($obj->compositeDatabaseFields() as $fieldName => $type) {
                $result[FieldAccessor::formatField($fieldName)] = $obj->$fieldName;
            }

            return $result;
        }

        return $obj;
    }
}
