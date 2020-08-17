namespace SilverStripe\\GraphQL\\Schema\\Generated\\Schema;

use GraphQL\\Type\\Definition\\Type;
use GraphQL\\Type\\Definition\\NonNull;
use GraphQL\\Type\\Definition\\ListOfType;

class $TypesClassName
{
    private static \$types = [];

    public static function get(string \$typename)
    {
        return function() use (\$typename) {
            return static::fromCache(\$typename);
        };
    }

    private static function fromCache(string \$typename)
    {
        \$type = null;

        if (!isset(self::\$types[\$typename])) {
            \$file = __DIR__ . '/' . \$typename . '.php';
            if (file_exists(\$file)) {
                require_once(\$file);
                if (class_exists(\$typename)) {
                    \$type = new \$typename();
                }
            }
            self::\$types[\$typename] = \$type;
        }


        \$type = self::\$types[\$typename];

        if (!\$type) {
            throw new \\Exception("Unknown graphql type: " . \$typename);
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
    public static function {$Name}() { return static::get('$Name'); }
    <% end_loop %>
    <% loop $Enums %>
    public static function {$Name}() { return static::get('$Name'); }
    <% end_loop %>
    <% loop $Interfaces %>
    public static function {$Name}() { return static::get('$Name'); }
    <% end_loop %>
    <% loop $Unions %>
    public static function {$Name}() { return static::get('$Name'); }
    <% end_loop %>


}
