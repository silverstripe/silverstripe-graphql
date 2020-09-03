namespace $Namespace;

use GraphQL\\Type\\Definition\\EnumType;


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
            'description' => '$DescriptionEscaped'
            <% end_if %>
        ]);
    }
}
