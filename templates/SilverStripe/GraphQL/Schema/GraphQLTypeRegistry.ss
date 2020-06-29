namespace SilverStripe\\GraphQL\\Schema\\Generated\\Schema_{$Hash};

use GraphQL\\Type\\Definition\\ObjectType;
use GraphQL\\Type\\Definition\\Type;
use GraphQL\\Type\\Definition\\NonNull;
use GraphQL\\Type\\Definition\\ListOfType;

<% loop $Types %>
class $Name extends ObjectType {
    public function __construct()
    {
        parent::__construct([
            'name' => '$Name',
            'fields' => function () {
                return [
                    <% loop $FieldList %>
                        [
                            'name' => '$Name',
                            'type' => $EncodedType,
                        <% if $ArgList %>
                            'args' => [
                                <% loop $ArgList %>
                                    [
                                        'name' => '$Name',
                                        'type' => $EncodedType
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
        }

        self::\$types[\$cacheName] = \$type;
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
}
