call_user_func_array(
    $Callable.RAW,
    [<% loop $ContextArgs %>$EncodedArg.RAW<% if not $Last %>,<% end_if %><% end_loop %>]
)
