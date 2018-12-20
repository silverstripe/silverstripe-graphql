
$SilverStripe_Assets_Storage_DBFile = new GraphQL\Type\Definition\ObjectType([
'name' => 'SilverStripe_Assets_Storage_DBFile',
'description' => NULL,
'fields' => function () {
return [
'Filename' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Filename',
'type' => Type::string(),
'resolve' => NULL,
]);,
'Hash' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Hash',
'type' => Type::string(),
'resolve' => NULL,
]);,
'Variant' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Variant',
'type' => Type::string(),
'resolve' => NULL,
]);,
'URL' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'URL',
'type' => Type::string(),
'resolve' => NULL,
]);,
'Width' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Width',
'type' => Type::int(),
'resolve' => NULL,
]);,
'Height' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Height',
'type' => Type::int(),
'resolve' => NULL,
]);,
];
},
]);


$AbrasiveCoatRange = new GraphQL\Type\Definition\ObjectType([
'name' => 'AbrasiveCoatRange',
'description' => NULL,
'fields' => function () {
return [
'ID' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ID',
'type' => Type::id(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'ClassName' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ClassName',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'LastEdited' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'LastEdited',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Created' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Created',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'CanViewType' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'CanViewType',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'CanEditType' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'CanEditType',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Version' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Version',
'type' => Type::int(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'URLSegment' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'URLSegment',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Title' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Title',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'MenuTitle' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'MenuTitle',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Content' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Content',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'MetaDescription' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'MetaDescription',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'ExtraMeta' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ExtraMeta',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'ShowInMenus' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ShowInMenus',
'type' => Type::boolean(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'ShowInSearch' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ShowInSearch',
'type' => Type::boolean(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Sort' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Sort',
'type' => Type::int(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'HasBrokenFile' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'HasBrokenFile',
'type' => Type::boolean(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'HasBrokenLink' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'HasBrokenLink',
'type' => Type::boolean(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'ReportClass' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'ReportClass',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Market' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Market',
'type' => Type::int(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Theory' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Theory',
'type' => Type::string(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
'Bells' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'Bells',
'type' => Type::boolean(),
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver',
1 => 'resolve',
),
]);,
];
},
]);


$readAbrasiveCoatRangesConnection = new GraphQL\Type\Definition\ObjectType([
'name' => 'readAbrasiveCoatRangesConnection',
'description' => NULL,
'fields' => function () {
return [
'pageInfo' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'pageInfo',
'type' => Type::nonNull($this->get('PageInfo')),
'description' => 'Pagination information',
'resolve' => NULL,
]);,
'edges' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'edges',
'type' => Type::listOf($this->get('readAbrasiveCoatRangesEdge')),
'description' => 'Collection of records',
'resolve' => NULL,
]);,
];
},
]);


$readAbrasiveCoatRangesEdge = new GraphQL\Type\Definition\ObjectType([
'name' => 'readAbrasiveCoatRangesEdge',
'description' => 'The collections edge',
'fields' => function () {
return [
'node' => new SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition([
'name' => 'node',
'type' => $this->get('AbrasiveCoatRange'),
'description' => 'The node at the end of the collections edge',
'resolve' => array (
0 => 'SilverStripe\\GraphQL\\Pagination\\Connection',
1 => 'nodeResolver',
),
]);,
];
},
]);


<!DOCTYPE html><html><head><title>GET /dev/schema/default</title><link rel="stylesheet" type="text/css" href="/resources/vendor/silverstripe/framework/client/styles/debug.css?m=1543873195" /></head><body><div class="header info error"><h1>[Emergency] Uncaught Error: Call to undefined method GraphQL\Type\Definition\InputObjectField::toPHP()</h1><h3>GET /dev/schema/default</h3><p>Line <strong>87</strong> in <strong>/var/www/mysite/www/vendor/silverstripe/graphql/src/Serialisation/SerialisableInputType.php</strong></p></div><div class="info"><h3>Source</h3><pre><span>78</span>             'fields',
<span>79</span>         ];
<span>80</span>     }
<span>81</span> 
<span>82</span>     public function toPHP($varName = null)
<span>83</span>     {
<span>84</span>         $this-&gt;assertSerialisable();
<span>85</span>         $fields = [];
<span>86</span>         foreach ($this-&gt;getFields() as $fieldName =&gt; $fieldDef) {
<span>87</span> <span class="error">            $fields[$fieldName] = new Expression($fieldDef-&gt;toPHP());
</span><span>88</span>         }
<span>89</span>         return new ConfigurableObjectInstantiator(
<span>90</span>             ObjectType::class,
<span>91</span>             [
<span>92</span>                 'name' =&gt; $this-&gt;name,
<span>93</span>                 'description' =&gt; $this-&gt;description,
</pre></div><div class="info"><h3>Trace</h3><ul><li><b>SilverStripe\GraphQL\Serialisation\SerialisableInputType-&gt;toPHP()</b>
            <br />
            Manager.php:772</li>
        <li><b>SilverStripe\GraphQL\Manager-&gt;regenerate()</b>
            <br />
            SchemaGeneratorController.php:76</li>
        <li><b>SilverStripe\GraphQL\Dev\SchemaGeneratorController-&gt;build(SilverStripe\Control\HTTPRequest)</b>
            <br />
            RequestHandler.php:323</li>
        <li><b>SilverStripe\Control\RequestHandler-&gt;handleAction(SilverStripe\Control\HTTPRequest, build)</b>
            <br />
            Controller.php:284</li>
        <li><b>SilverStripe\Control\Controller-&gt;handleAction(SilverStripe\Control\HTTPRequest, build)</b>
            <br />
            RequestHandler.php:202</li>
        <li><b>SilverStripe\Control\RequestHandler-&gt;handleRequest(SilverStripe\Control\HTTPRequest)</b>
            <br />
            Controller.php:212</li>
        <li><b>SilverStripe\Control\Controller-&gt;handleRequest(SilverStripe\Control\HTTPRequest)</b>
            <br />
            RequestHandler.php:226</li>
        <li><b>SilverStripe\Control\RequestHandler-&gt;handleRequest(SilverStripe\Control\HTTPRequest)</b>
            <br />
            Controller.php:212</li>
        <li><b>SilverStripe\Control\Controller-&gt;handleRequest(SilverStripe\Control\HTTPRequest)</b>
            <br />
            Director.php:361</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            VersionedHTTPMiddleware.php:41</li>
        <li><b>SilverStripe\Versioned\VersionedHTTPMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            BasicAuthMiddleware.php:68</li>
        <li><b>SilverStripe\Security\BasicAuthMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            AuthenticationMiddleware.php:61</li>
        <li><b>SilverStripe\Security\AuthenticationMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            CanonicalURLMiddleware.php:188</li>
        <li><b>SilverStripe\Control\Middleware\CanonicalURLMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            HTTPCacheControlMiddleware.php:42</li>
        <li><b>SilverStripe\Control\Middleware\HTTPCacheControlMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            ChangeDetectionMiddleware.php:27</li>
        <li><b>SilverStripe\Control\Middleware\ChangeDetectionMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            FlushMiddleware.php:29</li>
        <li><b>SilverStripe\Control\Middleware\FlushMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            RequestProcessor.php:66</li>
        <li><b>SilverStripe\Control\RequestProcessor-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            SessionMiddleware.php:20</li>
        <li><b>SilverStripe\Control\Middleware\SessionMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            AllowedHostsMiddleware.php:60</li>
        <li><b>SilverStripe\Control\Middleware\AllowedHostsMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            TrustedProxyMiddleware.php:176</li>
        <li><b>SilverStripe\Control\Middleware\TrustedProxyMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\Director-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            HTTPMiddlewareAware.php:65</li>
        <li><b>SilverStripe\Control\Director-&gt;callMiddleware(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            Director.php:370</li>
        <li><b>SilverStripe\Control\Director-&gt;handleRequest(SilverStripe\Control\HTTPRequest)</b>
            <br />
            HTTPApplication.php:48</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;SilverStripe\Control\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
        </li>
        <li><b>call_user_func(Closure, SilverStripe\Control\HTTPRequest)</b>
            <br />
            HTTPApplication.php:66</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;SilverStripe\Control\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
        </li>
        <li><b>call_user_func(Closure, SilverStripe\Control\HTTPRequest)</b>
            <br />
            ErrorControlChainMiddleware.php:73</li>
        <li><b>SilverStripe\Core\Startup\ErrorControlChainMiddleware-&gt;SilverStripe\Core\Startup\{closure}(SilverStripe\Core\Startup\ErrorControlChain)</b>
            <br />
        </li>
        <li><b>call_user_func(Closure, SilverStripe\Core\Startup\ErrorControlChain)</b>
            <br />
            ErrorControlChain.php:235</li>
        <li><b>SilverStripe\Core\Startup\ErrorControlChain-&gt;step()</b>
            <br />
            ErrorControlChain.php:225</li>
        <li><b>SilverStripe\Core\Startup\ErrorControlChain-&gt;execute()</b>
            <br />
            ErrorControlChainMiddleware.php:92</li>
        <li><b>SilverStripe\Core\Startup\ErrorControlChainMiddleware-&gt;process(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPMiddlewareAware.php:62</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;SilverStripe\Control\Middleware\{closure}(SilverStripe\Control\HTTPRequest)</b>
            <br />
            HTTPMiddlewareAware.php:65</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;callMiddleware(SilverStripe\Control\HTTPRequest, Closure)</b>
            <br />
            HTTPApplication.php:67</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;execute(SilverStripe\Control\HTTPRequest, Closure, )</b>
            <br />
            HTTPApplication.php:49</li>
        <li><b>SilverStripe\Control\HTTPApplication-&gt;handle(SilverStripe\Control\HTTPRequest)</b>
            <br />
            index.php:26</li>
    </ul></div></body></html>