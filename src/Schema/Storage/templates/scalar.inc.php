<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\Scalar $scalar */
/* @var array $globals */
?>
<?php $scalar = $scope; ?>
namespace <?=$globals['namespace']; ?>;

use GraphQL\Type\Definition\CustomScalarType;

// @type:<?=$scalar->getName(); ?>

class <?=$globals['obfuscator']->obfuscate($scalar->getName()) ?> extends CustomScalarType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '<?=$scalar->getName() ?>',
            'serialize' => <?=$scalar->getEncodedSerialiser()->getExpression() ?>,
            'parseValue' => <?=$scalar->getEncodedValueParser()->getExpression() ?>,
            'parseLiteral' => <?=$scalar->getEncodedLiteralParser()->getExpression() ?>,
        ]);
    }
}
