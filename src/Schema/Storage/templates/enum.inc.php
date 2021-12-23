<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\Enum $enum */
/* @var array $globals */
?>
<?php $enum = $scope; ?>
namespace <?=$globals['namespace']; ?>;

use GraphQL\Type\Definition\EnumType;

// @type:<?=$enum->getName(); ?>

class <?=$globals['obfuscator']->obfuscate($enum->getName()) ?> extends EnumType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '<?=$enum->getName(); ?>',
            'values' => [
        <?php foreach ($enum->getValueList() as $valueItem) : ?>
                '<?=$valueItem['Key']; ?>' => [
                    'value' => <?=var_export($valueItem['Value'], true) ?>,
                <?php if (!empty($valueItem['Description'])) : ?>
                    'description' => '<?=addslashes($valueItem['Description']); ?>',
                <?php endif; ?>
                ],
        <?php endforeach; ?>
            ],
        <?php if (!empty($enum->getDescription())) : ?>
            'description' => '<?=addslashes($enum->getDescription()); ?>',
        <?php endif; ?>
        ]);
    }
}
