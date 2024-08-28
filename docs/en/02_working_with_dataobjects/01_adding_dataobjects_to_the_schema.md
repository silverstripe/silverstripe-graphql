---
title: Adding DataObject models to the schema
summary: An overview of how the DataObject model can influence the creation of types, queries, and mutations
---

# Working with `DataObject` models

[CHILDREN asList]

## The `DataObject` model type

In Silverstripe CMS projects, our data tends to be contained in DataObjects almost exclusively,
and the `silverstripe/graphql` schema API is designed so that adding `DataObject` content to your
GraphQL schema definition is fast and simple.

### Using model types

While it is possible to add DataObjects to your schema as generic types under the `types`
section of the configuration, and their associated queries and mutations under `queries` and
`mutations`, this will lead to a lot of boilerplate code and repetition. Unless you have some
really custom needs, a much better approach is to embrace *convention over configuration*
and use the `models` section of the config.

**Model types** are types that rely on external classes to tell them who they are and what
they can and cannot do. The model can define and resolve fields, auto-generate queries
and mutations, and more.

Naturally, this module comes bundled with a model type for subclasses of `DataObject`.

Let's use the `models` config to expose some content.

```yml
# app/_graphql/models.yml
Page:
  fields: '*'
  operations: '*'
```

The class `Page` is a subclass of `DataObject`, so the bundled model
type will kick in here and provide a lot of assistance in building out this part of our API.

Case in point, by supplying a value of `*` for `fields` , we're saying that we want *all* of the fields
on the `Page` class. This includes the first level of relationships, as defined on `has_one`, `has_many`,
or `many_many`.

> [!WARNING]
> Fields on relationships will not inherit the `*` fields selector, and will only expose their ID by default.
> To add additional fields for those relationships you will need to add the corresponding `DataObject` model types.

The `*` value on `operations` tells the schema to create all available queries and mutations
 for the DataObject, including:

- `read`
- `readOne`
- `create`
- `update`
- `delete`

Now that we've changed our schema, we need to build it using the `graphql:build` command:

`vendor/bin/sake graphql:build --schema=default`

Now we can access our schema on the default GraphQL endpoint, `/graphql`.

Test it out!

> [!NOTE]
> Note the use of the default arguments on `date`. Fields created from `DBFields`
> generate their own default sets of arguments. For more information, see
> [DBFieldArgs](02_query_plugins.md#dbfieldargs).

**A query:**

```graphql
query {
  readPages {
    nodes {
      title
      content
      ... on BlogPage {
        date(format: NICE)
        comments {
          nodes {
            comment
            author {
              firstName
            }
          }
        }
      }
    }
  }
}
```

> [!NOTE]
> The `... on BlogPage` syntax is called an [inline fragment](https://graphql.org/learn/queries/#inline-fragments).
> You can learn more about this syntax in the [Inheritance](inheritance) section.

**A mutation:**

```graphql
mutation {
  createPage(input: {
    title: "my page"
  }) {
    title
    id
  }
}
```

> [!TIP]
> Did you get a permissions error? Make sure you're authenticated as someone with appropriate access.

### Configuring operations

You may not always want to add *all* operations with the `*` wildcard. You can allow those you
want by setting them to `true` (or `false` to remove them).

```yml
# app/_graphql/models.yml
Page:
  fields: '*'
  operations:
    read: true
    create: true

App\Model\Product:
  fields: '*'
  operations:
    '*': true
    delete: false
```

Operations are also configurable, and accept a nested map of config.

```yml
# app/_graphql/models.yml
Page:
  fields: '*'
  operations:
    create: true
    read:
      name: getAllThePages
```

#### Customising the input types

The input types, specifically in `create` and `update`, can be customised with a
list of fields. The list can include explicitly *disallowed* fields.

```yml
# app/_graphql/models.yml
Page:
  fields: '*'
  operations:
    create:
      fields:
        title: true
        content: true
    update:
      fields:
        '*': true
        immutableField: false
```

### Adding more fields

Let's add some more DataObjects, but this time, we'll only add a subset of fields and operations.

```yml
# app/_graphql/models.yml
Page:
  fields: '*'
  operations: '*'

App\Model\Product:
  fields:
    onSale: true
    title: true
    price: true
  operations:
    delete: true

App\Model\ProductCategory:
  fields:
    title: true
    featured: true
```

> [!WARNING]
> A couple things to note here:
>
> - By assigning a value of `true` to the field, we defer to the model to infer the type for the field. To override that, we can always add a `type` property:
>
>     ```yml
>     App\Model\Product:
>       fields:
>         onSale:
>           type: Boolean
>     ```
>
> - The mapping of our field names to the `DataObject` property is case-insensitive. It is a
> convention in GraphQL APIs to use lowerCamelCase fields, so this is given by default.

### Bulk loading models

It's likely that in your application you have a whole collection of classes you want exposed to the API with roughly
the same fields and operations exposed on them. It can be really tedious to write a new declaration for every single
`DataObject` in your project, and as you add new ones, there's a bit of overhead in remembering to add it to the
GraphQL schema.

Common use cases might be:

- Add everything in `App\Model`
- Add every implementation of `BaseElement`
- Add anything with the `Versioned` extension
- Add everything that matches `src/*Model.php`

You can create logic like this using the `bulkLoad` configuration file, which allows you to specify groups of directives
that load a bundle of classes and apply the same set of configuration to all of them.

```yml
# app/_graphql/bulkLoad.yml
elemental: # An arbitrary key to define what these directives are doing
  # Load all elemental blocks except MySecretElement
  load:
    inheritanceLoader:
      include:
        - DNADesign\Elemental\Models\BaseElement
      exclude:
        - App\Model\Elemental\MySecretElement
  # Add all fields and read operations
  apply:
    fields:
      '*': true
    operations:
      read: true
      readOne: true

app:
  # Load everything in our App\Model\ namespace that has the Versioned extension
  # unless the filename ends with .secret.php
  load:
    namespaceLoader:
      include:
        - App\Model\*
    extensionLoader:
      include:
        - SilverStripe\Versioned\Versioned
    filepathLoader:
      exclude:
        - app/src/Model/*.secret.php
  apply:
    fields:
      '*': true
    operations:
      '*': true
```

By default, four loaders are provided to you to help gather specific classnames:

#### By namespace

- **Identifier**: `namespaceLoader`
- **Description**: Include or exclude classes based on their namespace
- **Example**: `include: [App\Model\*]`

#### By inheritance

- **Identifier**: `inheritanceLoader`
- **Description**: Include or exclude everything that matches or extends a given base class
- **Example**: `include: [DNADesign\Elemental\Models\BaseElement]`

#### By applied extension

- **Identifier**: `extensionLoader`
- **Description**: Include or exclude any class that has a given extension applied
- **Example**: `include: [SilverStripe\Versioned\Versioned]`

#### By filepath

- **Identifier**: `filepathLoader`
- **Description**: Include or exclude any classes in files matching a given glob expression, relative to the base path. Module syntax is allowed.
- **Examples**:
  - `include: [ 'src/Model/*.model.php' ]`
  - `include: [ 'somevendor/somemodule: src/Model/*.php' ]`

> [!NOTE]
> `exclude` directives will always supersede `include` directives.

Each block starts with a collection of all classes that gets filtered as each loader runs. The primary job
of a loader is to *remove* classes from the entire collection, not add them in.

> [!NOTE]
> If you find that this paints with too big a brush, you can always override individual models explicitly in `models.yml`.
> The bulk loaders run *before* the `models.yml` config is loaded.

#### `DataObject` subclasses are the default starting point

Because this is Silverstripe CMS, and it's likely that you're using `DataObject` models only, the bulk loaders start with an
initial filter which is defined as follows:

```yml
inheritanceLoader:
  include:
    - SilverStripe\ORM\DataObject
```

This ensures that at a bare minimum, you're always filtering by `DataObject` classes *only*. If, for some reason, you
have a non-`DataObject` class in `App\Model\*`, it will automatically be filtered out due to this default setting.

This default is configured in the `defaultBulkLoad` setting in your schema config. Should you ever want to disable
that, just set it to `false`.

```yml
# app/_graphql/config.yml
defaultBulkLoad: false
```

#### Creating your own bulk loader

Bulk loaders must extend [`AbstractBulkLoader`](api:SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader). They
need to declare an identifier (e.g. `namespaceLoader`) to be referenced in the config, and they must implement
[`collect()`](api:SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader::collect()) which returns a new `Collection`
instance once the loader has done its work parsing through the `include` and `exclude` directives.

Bulk loaders are automatically registered. Just creating the class is all you need to do to have it available for use
in your `bulkLoad.yml` file.

### Customising model fields

You don't have to rely on the model to tell you how fields should resolve. Just like
generic types, you can customise them with arguments and resolvers.

```yml
# app/_graphql/models.yml
App\Model\Product:
  fields:
    title:
      type: String
      resolver: ['App\GraphQL\Resolver\ProductResolver', 'resolveSpecialTitle']
    'price(currency: String = "NZD")': true
```

For more information on custom arguments and resolvers, see the
[adding arguments](../working_with_generic_types/adding_arguments) and
[resolver discovery](../working_with_generic_types/resolver_discovery) documentation.

### Excluding or customising "*" declarations

You can use `*` as a field or operation, and anything that follows it will override the
all-inclusive collection. This is almost like a spread operator in JavaScript:

```js
const newObj = { ...oldObj, someProperty: 'custom' };
```

Here's an example:

```yml
# app/_graphql/models.yml
Page:
  fields:
    '*': true # Get everything
    sensitiveData: false # hide this field
    'content(summaryLength: Int)': true # add an argument to this field
  operations:
    '*': true
    read:
      plugins:
        paginateList: false # don't paginate the read operation
```

### Disallowed fields {#disallowed-fields}

While selecting all fields via `*` is useful, there are some fields that you
don't want to accidentally expose, especially if you're a module author
and expect models within this code to be used through custom GraphQL endpoints.
For example, a module might add a secret "preview token" to each `SiteTree`.
A custom GraphQL endpoint might have used `fields: '*'` on `SiteTree` to list pages
on the public site, which now includes a sensitive field.

The `graphql_blacklisted_fields` property on `DataObject` allows you to
disallow fields globally for all GraphQL schemas.
This block list applies for all operations (read, update, etc).

```yml
# app/_config/graphql.yml
SilverStripe\CMS\Model\SiteTree:
  graphql_blacklisted_fields:
    myPreviewTokenField: true
```

### Model configuration

There are several settings you can apply to your model class (typically `DataObjectModel`),
but because they can have distinct values *per schema*, the standard `_config` layer is not
an option. Model configuration has to be done within the schema config in the `modelConfig`
subsection.

### Customising the type name

Most `DataObject` classes are namespaced, so converting them to a type name ends up
being very verbose. As a default, the `DataObjectModel` class will use the "short name"
of your `DataObject` as its typename (see: [`ClassInfo::shortName()`](api:SilverStripe/Core/ClassInfo::shortName())).
That is, `App\Model\Product` becomes `Product`.

Given the brevity of these type names, it's not inconceivable that you could run into naming
collisions, particularly if you use feature-based namespacing. Fortunately, there are
hooks you have available to help influence the typename.

#### Explicit type mapping

You can explicitly provide type name for a given class using the `typeMapping` setting in your schema config.

```yml
# app/_graphql/config.yml
typeMapping:
  App\PageType\Page: SpecialPage
```

It may be necessary to use `typeMapping` in projects that have a lot of similar class names in different namespaces, which will cause a collision
when the type name is derived from the class name. The most case for this
is the `Page` class, which may be both at the root namespace and in your
app namespace, e.g. `App\PageType\Page`.

#### The type formatter

The `type_formatter` is a callable that can be set on the `DataObjectModel` config. It takes
the `$className` as a parameter.

Let's turn the type for `App\Model\Product` from `Product` into the more specific `AppProduct`

```yml
# app/_graphql/config.yml
modelConfig:
  DataObject:
    type_formatter: ['App\GraphQL\Formatter', 'formatType']
```

> [!NOTE]
> In the above example, `DataObject` is the result of [`DataObjectModel::getIdentifier()`](api:SilverStripe\GraphQL\Schema\DataObject::getIdentifier()).
> Each model class must declare one of these.

The formatting function in your `App\GraphQL\Formatter` class could look something like:

```php
namespace App\GraphQL;

class Formatter
{
    public static function formatType(string $className): string
    {
        $parts = explode('\\', $className);
        if (count($parts) === 1) {
            return $className;
        }
        $first = reset($parts);
        $last = end($parts);

        return $first . $last;
    }
}
```

#### The type prefix

You can also add prefixes to all your `DataObject` types. This can be a scalar value or a callable,
using the same signature as `type_formatter`.

```yml
# app/_graphql/config.yml
modelConfig:
  DataObject:
    type_prefix: 'App'
```

This would automatically set the type name for your `App\Model\Product` class to `AppProduct`
without needing to declare a `type_formatter`.

### Further reading

[CHILDREN]
