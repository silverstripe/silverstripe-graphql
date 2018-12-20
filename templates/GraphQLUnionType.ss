new $ClassName([
  'name' => '$Name',
<% if $Description %>
   'description' => '$Description',
<% end_if %>
   'types' => function () {
        return [
<% loop $Types %>
          $Expression.RAW,
<% end_loop %>
        ];
   },
   <% if $ResolveTypeFactory %>
   'resolveTypeFactory' => $ResolveTypeFactory.RAW,
    <% end_if %>
    <% else_if $ResolveType %>
   'resolveType' => $ResolveType.RAW,
    <% end_if %>
]);