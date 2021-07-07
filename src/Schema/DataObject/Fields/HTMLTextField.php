<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Fields;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\DataObject\Resolver;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Argument;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\SS_List;

class HTMLTextField implements FieldCreator
{
    public function createField(DBField $dbField, ModelField $graphqlField): ModelField
    {
        Schema::invariant(
            $dbField instanceof DBHTMLText,
            '%s requires instances of %s. Got %s',
            static::class,
            DBHTMLText::class,
            get_class($dbField)
        );
        return $graphqlField
            ->setType('String')
            ->addArg('parseShortcodes', 'Boolean', function (Argument $arg) {
                $arg->setDescription('If true, convert the CMS shortcodes to HTML')
                    ->setDefaultValue(false);
            })
            ->setResolver([static::class, 'resolve']);
    }

    /**
     * @param DataObject $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $info
     * @return array|bool|int|mixed|DataList|DataObject|DBField|SS_List|string|null
     * @throws SchemaBuilderException
     */
    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        $fieldName = $info->fieldName;
        $config = SchemaConfigProvider::get($context);
        $resolvedField = Resolver::getResolvedField($obj, $fieldName, $config);
        if (!$resolvedField) {
            return null;
        }
        if ($resolvedField instanceof DBHTMLText) {
            $resolvedField->setProcessShortcodes($args['parseShortcodes']);
            return $resolvedField->getValue();
        }

        return $resolvedField;
    }
}
