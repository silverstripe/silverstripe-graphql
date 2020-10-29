<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\UnionType $union */
/* @var array $globals */
?>
<?php $union = $scope; ?>
namespace <?=$globals['namespace'] ?>;

use GraphQL\Type\Definition\UnionType;

class <?=$union->getName() ?> extends UnionType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '<?=$union->getName(); ?>',
            'types' => function () {
                return array_map(function ($type) {
                    return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName'] ?>', $type]);
                }, <?=$union->getEncodedTypes(); ?>);
            },
            'resolveType' => function ($obj) {
                $type = call_user_func_array(<?=$union->getEncodedTypeResolver()->encode(); ?>, [$obj]);
                return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName'] ?>', $type]);
            },
        <?php if (!empty($union->getDescription())) : ?>
            'description' => '<?=addslashes($union->getDescription()); ?>',
        <?php endif; ?>
        ]);
    }
}
