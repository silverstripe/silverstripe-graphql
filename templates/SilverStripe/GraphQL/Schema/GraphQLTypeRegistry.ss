namespace $Namespace;

use SilverStripe\\GraphQL\\Schema\\Storage\\AbstractTypeRegistry;

class $TypesClassName extends AbstractTypeRegistry
{
    protected static function getSourceDirectory(): string
    {
        return __DIR__;
    }

    protected static function getSourceNamespace(): string
    {
        return __NAMESPACE__;
    }

    <% loop $SchemaComponents %>
    public static function {$Name}() { return static::get('$Name'); }
    <% end_loop %>

}
