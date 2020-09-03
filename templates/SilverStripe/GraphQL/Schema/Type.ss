namespace $Namespace;

use GraphQL\\Type\\Definition\\ObjectType;
use GraphQL\\Type\\Definition\\InputObjectType;
use SilverStripe\\GraphQL\\Schema\\Resolver\\ComposedResolver;


class $Name extends <% if $IsInput %>InputObjectType<% else %>ObjectType<% end_if %>
{
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
        <% if $Description %>
            'description' => '$DescriptionEscaped',
        <% end_if %>
        <% if $Interfaces %>
            'interfaces' => function () {
                return array_map(function (\$interface) {
                    return call_user_func([__NAMESPACE__ . '\\{$Top.TypesClassName}', \$interface]);
                }, $EncodedInterfaces.RAW);
            },
        <% end_if %>
            'fields' => function () {
                return [
                <% loop $FieldList %>
                    [
                        'name' => '$Name',
                        'type' => $EncodedType,
                        'resolve' => $getEncodedResolver($Up.Name),
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

