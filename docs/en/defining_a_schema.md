# Defining a schema

GraphQL is a strongly-typed API layer, so having a schema behind it is essential. Simply put:

* A schema consists of **[types](https://graphql.org/learn/schema/#type-system)**
* **Types** consist of **[fields](https://graphql.org/learn/queries/#fields)**
* **Fields** can have **[arguments](https://graphql.org/learn/queries/#arguments)**.
* **Fields** need to **[resolve](https://graphql.org/learn/execution/#root-fields-resolvers)**
* **Queries** are just **fields** on a type called "query". They can take arguments, and they
must resolve.

There's a bit more to it than that, and if you want to learn more about GraphQL, you can read
the [full documentation](https://graphql.org/learn/), but for now, these three concepts will
serve almost all of your needs to get started.

## Initial setup

To start your first schema, open a new configuration file. Let's call it `graphql.yml`.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    # your schemas here
```

Let's populate schema that is pre-configured for us out of the box, `default`.

**app/_config/graphql.yml**
```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        # your types here
      queries:
        # your queries here
      mutations:
        # your mutations here
```

Let's get started by [defining some types](defining_types.md).


## Defining types

In this section, we'll define some generic types for our GraphQL schema.

**NB**: this tutorial will not cover adding dataobjects to your schema. For dataobject types,
see the section on [model-backed types](../models).

### Types are just YAML structures

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        MyType:
          fields:
            myField: String
            myOtherField: Int
            myList: '[String]'
```

If you're familiar with [GraphQL type language](https://graphql.org/learn/schema/#type-language), this should look pretty familiar. There are only a handful of scalar types available in
GraphQL by default. They are:

* String
* Int
* Float
* Boolean

To define a type as a list, you wrap it in brackets: `[String]`, `[Int]`

To define a type as required (non-null), you add an exclamation mark: `String!`

Often times, you may want to do both: `[String!]!`

> Look out for the footgun, here. Make sure your bracketed type is in quotes, otherwise it's valid YAML that will get parsed as an array!

That's all there is to it!

#### A more realistic example

Let's create a simple type that will work with the inbuilt features of Silverstripe CMS.
We'll define some languages based on the `i18n` API.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
```

We've defined a type called `Country` that has two fields: `code` and `name`. An example record
could be something like:

```
[
    'code' => 'bt',
    'name' => 'Bhutan'
]
```

## Defining queries

We've now defined the shape of our data, now we need to build a way to access it. For this,
we'll need a query.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
      queries:
        readCountries: '[Country]'
```

Now we have a query that will return all the countries. In order to make this work, we'll
need a **resolver**. For this, we're going to have to break out of the configuration layer
and write some code.

*app/src/Resolvers/MyResolver.php**
```
class MyResolver
{
    public static function resolveCountries(): array
    {
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name
            ];
        }

        return $results;
    }
}
```

Resolvers are pretty loosely defined, and don't have to adhere to any specific contract
other than that they **must be static methods**. You'll see why when we add it to the configuration:


```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            code: String!
            name: String!
      queries:
        readCountries:
          type: '[Country]'
          resolver: [ 'MyResolver', 'resolveCountries' ]
```

Now, we just have to build the schema:


Let's test this out in our GraphQL IDE. If you have the [graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools) module installed, just open it up and set it to the `/graphql` endpoint.

As you start typing, it should autocomplete for you.

Here's our query:
```graphql
query {
  readCountries {
    name
    code
  }
}
```

And the expected response:

```json
{
  "data": {
    "readCountries": [
      {
        "name": "Afghanistan",
        "code": "af"
      },
      {
        "name": "Åland Islands",
        "code": "ax"
      },
      // ... etc
    ]
  }
}
```

This is great, but as we write more and more queries for types with more and more fields,
it's going to get awfully laborious mapping all these resolvers. Let's clean this up a bit by
adding a bit of convention over configuration, and save ourselves a lot of time to boot.

### The resolver discovery pattern

When you define a query mutation, or any other field on a type, you can opt out of providing
an explicit resolver and allow the system to discover one for you based on naming convention.

Let's start by registering a resolver class(es) where we can define a bunch of these functions.

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\Schema\Registry\ResolverRegistry:
    constructor:
      myResolver: '%$MyProject\Resolvers\MyResolvers'
```

What we're registering here is called a `ResolverProvider`, and it must implement that interface.
The only thing this class is obliged to do is return a method name for a resolver given a type name and
`Field` object. If the class does not contain a resolver for that combination, it may return null and
defer to other resolver providers, or ultimately fallback on the global default resolver.

```php
public static function getResolverMethod(?string $typeName = null, ?Field $field = null): ?string;
```

Let's look at our query again:

```graphql
query {
  readCountries {
    name
  }
}
```

An example implementation of this method for our example might be:

* Does `resolveCountryName` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolveCountry` exist?
  * Yes? Invoke
  * No? Continue
* Does `resolveName` exist?
  * Yes? Invoke
  * No? Continue
* Return null. Maybe someone else knows how to deal with this.

You can implement whatever logic you like to help the resolver provider discover which of its methods
it appropriate for the given type/field combination, but since the above pattern seems like a pretty common
implementation, the module ships an abstract `DefaultResolverProvider` that applies this logic. You can just
write the resolver methods!

Let's add a resolver method to our resolver provider:

**app/src/Resolvers/MyResolvers.php**
```php
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverProvider;

class MyResolvers extends DefaultResolverProvider
{
    public static function resolveReadCountries()
    {
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name
            ];
        }

        return $results;
    }
}
```

Now that we're using logic to discover our resolver, we can clean up the config a bit.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        readCountries: '[Country]'
```

Re-run the schema build, with a flush, and let's go!

`$ vendor/bin/sake dev/tasks/build-schema schema=default flush=1`


### Field resolvers

A less magical approach to resolver discovery is defining a `fieldResolver` property on your
types. This is a generic handler for all fields on a given type and can be a nice middle
ground between the rigor of hard coding everything and the opacity of a discovery logic.

```yml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          fields:
            name: String
            code: String
          fieldResolver: [ 'MyProject\MyResolver', 'resolveCountryField' ]
```

You'll need to do explicit checks for the `fieldName` in your resolver to make this work.

```php
public static function resolveCountryField($obj, $args, $context, ResolveInfo $info)
{
    $fieldName = $info->fieldName;
    if ($fieldName === 'image') {
        return $obj->getImage()->getURL();
    }
    // .. etc
}
```

## Adding arguments

As stated above, fields can have arguments, and queries are just fields, so let's add a simple
way of influencing the query response:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        'readCountries(limit: Int!)': '[Country]'
```

We've provided the required argument `limit` to the query, which will allow us to truncate the results.
Let's update the resolver accordingly.

```php
    public static function resolveReadCountries($obj, array $args = [])
    {
        $limit = $args['limit'];
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        $countries = array_slice($countries, 0, $limit);
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name
            ];
        }

        return $results;
    }

```

Now let's try our query again. This time, notice that the IDE is telling us we're missing a required argument.

```graphql
query {
  readCountries(limit: 5) {
    name
    code
  }
}
```

This works pretty well, but maybe it's a bit over the top to *require* the `limit` argument. We want to optimise
performance, but we also don't want to burden the developer with tedium like this. Let's give it a default value.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      # ...
      queries:
        'readCountries(limit: Int = 20)': '[Country]'
```

Rebuild the schema, and notice that the IDE is no longer yelling at you for a `limit` argument.

## Enum types

Enum types are simply a list of string values that are possible for a given field. They are
often used in arguments to queries, such as `{sort: DESC }`.

It's very easy to add enum types to your schema. Just use the `enums` section of the config.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      enums:
        SortDirection:
          DESC: Descending order
          ASC: Ascending order
```

## Unions and interfaces

In more complex schemas, you may want to define types that aren't simply a list of fields, or
"object types." These include unions and interfaces.

### Interfaces

An interface is a specification of fields that must be included on a type that implements it.
For example, an interface `Person` could include `firstName: String`, `surname: String`, and
`age: Int`. The types `Actor` and `Chef` would implement the `Person` interface. Actors and
chefs must have names and ages.

To define an interface, use the `interfaces` section of the config.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      interfaces:
        Person:
          fields:
            firstName: String!
            surname: String!
            age: Int!
          resolveType: [ 'MyProject\MyResolver', 'resolvePersonType' ]
```

Interfaces must define a `resolveType` resolver method to inform the interface
which type it is applied to given a specific result. This method is non-discoverable and
must be applied explicitly.

```php
    public static function resolvePersonType($object): string
    {
        if ($object instanceof Actor) {
            return 'Actor';
        }
        if ($object instanceof Chef) {
            return 'Chef';
        }
    }
```

### Union types

A union type is used when a field can resolve to multiple types. For example, a query
for "Articles" could return a list containing both "Blog" and "NewsStory" types.

To add a union type, use the `unions` section of the configuration.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      unions:
        Article:
          types: [ 'Blog', 'NewsStory' ]
          typeResolver: [ 'MyProject\MyResolver', 'resolveArticleUnion' ]
```

Like interfaces, unions need to know how to resolve their types. These methods are also
non-discoverable and must be applied explicitly.

```php
    public static function resolveArticleUnion(Article $object): string
    {
        if ($object->category === 'blogs')
            return 'Blog';
        }
        if ($object->category === 'news') {
            return 'NewsStory';
        }
    }
```


## The global schema

Developers of thirdparty modules that influence graphql schemas may want to take advantage
of the _global schema_. This is a pseudo-schema that will merge itself with all other schemas
that have been defined. A good use case is in the `silverstripe/versioned` module, where it
is critical that all schemas can leverage its schema modifications.

The global schema is named `*`.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    '*':
      enums:
        VersionedStage:
          DRAFT: DRAFT
          LIVE: LIVE
```
