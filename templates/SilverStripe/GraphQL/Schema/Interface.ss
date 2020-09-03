namespace $Namespace;

use GraphQL\\Type\\Definition\\InterfaceType;


class $Name extends InterfaceType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
            'resolveType' => function (\$obj) {
                \$type = call_user_func_array($EncodedTypeResolver, [\$obj]);
                return call_user_func([__NAMESPACE__ . '\\{$Top.TypesClassName}', \$type]);
            },
            <% if $Description %>
            'description' => '$DescriptionEscaped',
            <% end_if %>
            'fields' => function () {
                return [
                <% loop $FieldList %>
                    [
                        'name' => '$Name',
                        'type' => $EncodedType,
                    <% if $Description %>
                        'description' => '$DescriptionEscaped',
                    <% end_if %>
                    <% if $ArgList %>
                        'args' => [
                        <% loop $ArgList %>
                            [
                                'name' => '$Name',
                                'type' => $EncodedType,
                            <% if $DefaultValue %>
                                'defaultValue' => $DefaultValue.RAW,
                            <% end_if %>
                            ],
                        <% end_loop %>
                        ],
                    <% end_if %>
                    ],
                <% end_loop %>
                ];
            }
        ]);
    }
}
