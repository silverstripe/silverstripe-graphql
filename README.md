# SilverStripe GraphQL Server

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-graphql)
[![codecov](https://codecov.io/gh/silverstripe/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-graphql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/?branch=master)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) representations,
with helpers to generate schemas based on SilverStripe model introspection.
It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library.
The APIs are very similar, for example

## Installation

Require the [composer](http://getcomposer.org) package in your `composer.json`

```
composer require silverstripe/graphql
```

## Usage

GraphQL is used through a single route which defaults to `/graphql`.
You need to define *Types* and *Queries* to expose your data via this endpoint.

## Examples

Code examples can be found in the `examples/` folder
(built out from the configuration docs below).

## Configuration

### Define Types

Types describe your data. While your data could be any arbitrary structure,
in a SilverStripe project a GraphQL type usually relates to a `DataObject`.
GraphQL uses this information to validate queries and allow GraphQL
clients to introspect your API capabilities. The GraphQL type system is hierarchical,
so the `fields()` definition declares object properties as scalar types within
your complex type. Refer to the [graphql-php type definitions](https://github.com/webonyx/graphql-php#type-system)
for available types.

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeCreator;

class MemberTypeCreator extends TypeCreator
{

    public function attributes()
    {
        return [
            'name' => 'member'
        ];
    }

    public function fields()
    {
        return [
            'ID' => ['type' => Type::nonNull(Type::id())],
            'Email' => ['type' => Type::string()],
            'FirstName' => ['type' => Type::string()],
            'Surname' => ['type' => Type::string()],
        ];
    }

}
```

Each type class needs to be registered with a unique name against the schema
through YAML configuration:

```yml
SilverStripe\GraphQL:
  schema:
    types:
      member: 'MyProject\GraphQL\MemberTypeCreator'
```

### Define Queries

Types can be exposed via "queries". These queries are in charge of retrieving
data through the SilverStripe ORM. The response itself is handled by the
underlying GraphQL PHP library, which loops through the resulting `DataList`
and accesses fields based on the referred "type" definition.

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;
use MyProject\MyDataObject;
use SilverStripe\Security\Member;

class ReadMembersQueryCreator extends QueryCreator
{

    public function attributes()
    {
        return [
            'name' => 'readMembers'
        ];
    }

    public function args() {
        return [
            'Email' => ['type' => Type::string()]
        ];
    }

    public function type()
    {
        // Return a "thunk" to lazy load types
        return function() {
            return Type::listOf($this->manager->getType('member'));
        };
    }


    public function resolve($args)
    {
        $list = Member::get();

        // Optional filtering by properties
        if(isset($args['Email'])) {
            $list = $list->filter('Email', $args['Email']);
        }

        return $list;
    }
}
```

We'll register the query with a unique name through YAML configuration:

```yml
SilverStripe\GraphQL:
  schema:
    queries:
      readMembers: 'MyProject\GraphQL\ReadMembersQueryCreator'
```

You can query data with the following URL:

```
/graphql?query=query+readMembers{members{ID,Email,FirstName,Surname}}
```

The query contained in the `query` parameter can be reformatted as follows:

```
query {
  readMembers {
    ID
    Email
    FirstName
    Surname
  }
}
```

### Define Mutations

A "mutation" is a specialised GraphQL query which has side effects on your data,
such as create, update or delete. Each of these operations would be expressed
as its own mutation class. Returning an object from the `resolve()` method
will automatically include it in the response.

```
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\Security\Member;


class CreateMemberMutationCreator extends MutationCreator
{

   public function attributes()
   {
        return [
            'name' => 'createMember',
            'description' => 'Creates a member without permissions or group assignments'
        ];
    }

    public function type()
    {
        return function() {
            return $this->manager->getType('member');
        };
    }

    public function args()
    {
        return [
            'Email' => ['type' => Type::nonNull(Type::string())],
            'FirstName' => ['type' => Type::string()],
            'LastName' => ['type' => Type::string()],
        ];
    }

    public function resolve($object, array $args, $context, $info)
    {
        if(!singleton(Member::class)->canCreate()) {
            throw new \InvalidArgumentException('Member creation not allowed');
        }

        return (new Member($args))->write();
    }
}

```

We'll register this mutation through YAML configuration:

```
yml
SilverStripe\GraphQL:
  schema:
    mutations:
      createMember: 'MyProject\GraphQL\CreateMemberMutationCreator'
```

You can run a mutation with the following query:

```
mutation($Email:String!) {
  createMember(Email:$Email) {
    ID
  }
}
```

This will create a new member with an email address,
which you can pass in as query variables: `{"Email": "test@test.com"}`.
It'll return the new `ID` property of the created member.

### Define Interfaces

TODO

### Define Input Types

TODO

## Testing/Debugging Queries and Mutations

This module comes bundled with an implementation of [graphiql](https://github.com/graphql/graphiql), an in-browser IDE for GraphQL servers. It provides browseable documentation of your schema, as well as autocomplete and syntax-checking of your queries.

This tool is available in **dev mode only**. It can be accessed at `/dev/graphiql/`.

<img src="https://github.com/graphql/graphiql/raw/master/resources/graphiql.png">

## TODO

 * Permission checks
 * Input/constraint validation on mutations (with third-party validator)
 * Pagination
 * CSRF protection (or token-based auth)
 * Generate CRUD operations based on DataObject reflection
 * Generate DataObject relationship CRUD operations
 * Create Enum GraphQL types from DBEnum
 * Date casting
 * Schema serialisation/caching (performance)
