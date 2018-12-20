new $ClassName([
  'name' => '$Name',
<% if $Description %>
  'description' => '$Description',
<% end_if %>
  'fields' => function () {
    return [
      <% loop $Fields %>
        '$Name' => <% with $Expression %>new $ClassName([
          'name' => '$Name',
          'type' => $Type.RAW,
          <% if $Description %>
           'description' => '$Description',
          <% end_if %>
        <% if $DeprecationReason %>
            'deprecationReason' => '$DeprecationReason',
        <% end_if %>
        <% if $ResolverFactory %>
            'resolverFactory' => $ResolverFactory.RAW,
        <% end_if %>
        <% if $Resolver %>
            'resolve' => $Resolver.RAW,
        <% end_if %>

          <% if $Args %>
          'args' => [
            <% loop $Args %>
              new $ClassName([
                'name' => '$Name',
                'type' => $Type.RAW,
                <% if $Description %>
                'description' => '$Description',
                <% end_if %>
              <% if $DefaultValue %>
                'defaultValue' => '$DefaultValue',
              <% end_if %>
            <% end_loop %>
          ],
          <% end_if %>
        ])<% end_with %>,
      <% end_loop %>
    ];
  },
]);