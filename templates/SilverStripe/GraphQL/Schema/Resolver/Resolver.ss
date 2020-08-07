<% if $ResolverMiddlewares || $ResolverAfterwares %>
ComposedResolver::create(
    $Expression.RAW,
    [
    <% loop $ResolverMiddlewares %>
        $Expression.RAW,
    <% end_loop %>
    ],
    [
    <% loop $ResolverAfterwares %>
        $Expression.RAW,
    <% end_loop %>
    ]
)
<% else %>
    $Expression.RAW
<% end_if %>
