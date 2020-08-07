namespace SilverStripe\\GraphQL\\Schema\\Generated\\Schema_{$Hash};

use GraphQL\\Type\\Definition\\ObjectType;
use GraphQL\\Type\\Definition\\InterfaceType;
use GraphQL\\Type\\Definition\\UnionType;
use GraphQL\\Type\\Definition\\InputObjectType;
use GraphQL\\Type\\Definition\\EnumType;
use GraphQL\\Type\\Definition\\Type;
use GraphQL\\Type\\Definition\\NonNull;
use GraphQL\\Type\\Definition\\ListOfType;
use SilverStripe\\GraphQL\\Schema\\Resolver\\ComposedResolver;

<% loop $Types %>
class $Name extends <% if $IsInput %>InputObjectType<% else %>ObjectType<% end_if %>  {
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
            <% if $Description %>
            'description' => '$Description',
            <% if $Interfaces %>
            'interfaces' => function () {
                return array_map(function (\$interface) {
                    return call_user_func([__NAMESPACE__ . '\\{$Top.TypesClassName}', \$interface]);
                }, $EncodedInterfaces.RAW);
            },
            <% end_if %>
            <% end_if %>
            'fields' => function () {
                return [
                    <% loop $FieldList %>
                        [
                            'name' => '$Name',
                            'type' => $EncodedType,
                            'resolve' => $getEncodedResolver($Up.Name),
                            <% if $Description %>
                            'description' => '$Description',
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

<% end_loop %>

<% loop $Interfaces %>
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
            'description' => '$Description',
            <% end_if %>
            'fields' => function () {
                return [
                <% loop $FieldList %>
                    [
                    'name' => '$Name',
                    'type' => $EncodedType,
                    <% if $Description %>
                        'description' => '$Description',
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
<% end_loop %>

<% loop $Unions %>
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
<% end_loop %>

<% loop $Enums %>
class $Name extends EnumType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
            'values' => [
                <% loop $ValueList %>
                '$Key' => ['value' => '$Value',<% if $Description %> 'description' => '$Description'<% end_if %>],
                <% end_loop %>
            ],
            <% if $Description %>
            'description' => '$Description'
            <% end_if %>
        ]);
    }
}
<% end_loop %>

class $TypesClassName
{
    private static \$types = [];

    public static function get(string \$classname)
    {
        return function() use (\$classname) {
            return static::fromCache(\$classname);
        };
    }

    private static function fromCache(string \$classname)
    {
        \$parts = explode("\\\\", \$classname);
        \$cacheName = \$parts[count(\$parts) - 1];
        \$type = null;

        if (!isset(self::\$types[\$cacheName])) {
            if (class_exists(\$classname)) {
                \$type = new \$classname();
            }
            self::\$types[\$cacheName] = \$type;
        }


        \$type = self::\$types[\$cacheName];

        if (!\$type) {
            throw new \\Exception("Unknown graphql type: " . \$classname);
        }

        return \$type;
    }

    public static function ID() { return Type::id(); }
    public static function String() { return Type::string(); }
    public static function Boolean() { return Type::boolean(); }
    public static function Float() { return Type::float(); }
    public static function Int() { return Type::int(); }
    public static function listOf(\$type) { return new ListOfType(\$type); }
    public static function nonNull(\$type) { return new NonNull(\$type); }

    <% loop $Types %>
    public static function {$Name}() { return static::get({$Name}::class); }
    <% end_loop %>
    <% loop $Enums %>
    public static function {$Name}() { return static::get({$Name}::class); }
    <% end_loop %>
    <% loop $Interfaces %>
    public static function {$Name}() { return static::get({$Name}::class); }
    <% end_loop %>
    <% loop $Unions %>
    public static function {$Name}() { return static::get({$Name}::class); }
    <% end_loop %>


}
