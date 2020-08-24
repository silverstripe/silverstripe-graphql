namespace $Namespace;

use GraphQL\\Type\\Definition\\UnionType;


class $Name extends UnionType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
            'types' => function () {
                return array_map(function (\$type) {
                    return call_user_func([__NAMESPACE__ . '\\{$Top.TypesClassName}', \$type]);
                }, $EncodedTypes.RAW);
            },
            'resolveType' => function (\$obj) {
                \$type = call_user_func_array($EncodedTypeResolver, [\$obj]);
                return call_user_func([__NAMESPACE__ . '\\{$Top.TypesClassName}', \$type]);
            },
            <% if $Description %>
                'description' => '$Description',
            <% end_if %>
        ]);
    }
}
