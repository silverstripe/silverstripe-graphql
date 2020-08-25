# SilverStripe GraphQL Server

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-graphql)
[![codecov](https://codecov.io/gh/silverstripe/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-graphql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/?branch=master)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
representations, with helpers to generate schemas based on SilverStripe model
introspection. It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library. The APIs are
very similar.

## Installation

Require the [composer](http://getcomposer.org) package in your `composer.json`

```
composer require silverstripe/graphql
```
## Building

This module requires a build task to generate the code for your schema(s). You must run the following
task whenever your schema changes:

```
vendor/bin/sake dev/tasks/build-schema schema=<schemaName>
```

If `schema` is not provided, the task will generate all schemas.

## Table of contents

- [Activating a new GraphQL server](activating.md)
- [Quickstart: Exposing DataObjects](quickstart)
- [Upgrading from v3](upgrading.md)
- [Defining a schema](defining_a_schema/index.md)
- [Building and consuming your API](building_and_consuming.md)
- [DataObjects and model-backed types](models/index.md)
  - [Creating types based on DataObjects](models/dataobjects.md)
  - [Adding queries and mutations for a DataObject](models/dataobject_queries_mutations.md)
  - [DataObject plugins](models/plugins/index.md)
    - [Filtering](models/plugins/filtering.md)
    - [Sorting](models/plugins/sorting.md)
    - [Pagination](models/plugins/pagination.md)
    - [Inheritance](models/plugins/inheritance.md)
    - [Versioning](models/plugins/versioning.md)
  - [Adding a custom model](models/custom.md)
- [Using procedural code](procedural.md)
- [Plugins](plugins/index.md)
  - [Writing a custom plugin](plugins/custom.md)
  - [Removing a plugin](plugins/removing.md)
 - [Authentication](#authentication)
   - [Default authentication](#default-authentication)
   - [HTTP basic authentication](#http-basic-authentication)
     - [In GraphiQL](#in-graphiql)
   - [Defining your own authenticators](#defining-your-own-authenticators)
 - [CSRF tokens (required for mutations)](#csrf-tokens-required-for-mutations)
 - [Cross-Origin Resource Sharing (CORS)](#cross-origin-resource-sharing-cors)
   - [Sample Custom CORS Config](#sample-custom-cors-config)
 - [Persisting Queries](#persisting-queries)
 - [Schema introspection](#schema-introspection)
 - [Setting up a new GraphQL schema](#setting-up-a-new-graphql-schema)
 - [Strict HTTP Method Checking](#strict-http-method-checking)







 - [Configuration](#configuration)
   - [Define types](#define-types)
   - [Define queries](#define-queries)
   - [Pagination](#pagination)
     - [Setting pagination and sorting options](#setting-pagination-and-sorting-options)
   - [Nested connections](#nested-connections)
   - [Adding search params](#adding-search-params)
   - [Define Mutations](#define-mutations)
 - [Scaffolding DataObjects into the schema](#scaffolding-dataobjects-into-the-schema)
   - [Our example](#our-example)
   - [Scaffolding through the config layer](#scaffolding-through-the-config-layer)
   - [Scaffolding through procedural code](#scaffolding-through-procedural-code)
   - [Exposing a DataObject to GraphQL](#exposing-a-dataobject-to-graphql)
     - [Available operations](#available-operations)
     - [Scaffolding search params](#adding-search-params-read-operations-only)
     - [Setting field and operation descriptions](#setting-field-and-operation-descriptions)
     - [Setting field descriptions](#setting-field-descriptions)
     - [Wildcarding and whitelisting fields](#wildcarding-and-whitelisting-fields)
     - [Adding arguments](#adding-arguments)
     - [Argument definition shorthand](#argument-definition-shorthand)
     - [Adding more definition to arguments](#adding-more-definition-to-arguments)
     - [Using a custom resolver](#using-a-custom-resolver)
     - [Configuring pagination and sorting](#configuring-pagination-and-sorting)
     - [Adding related objects](#adding-related-objects)
     - [Adding arbitrary queries and mutations](#adding-arbitrary-queries-and-mutations)
     - [Dealing with inheritance](#dealing-with-inheritance)
     - [Querying types that have descendants](#querying-types-that-have-descendants)
     - [Customising the names of types and operations](#customising-the-names-of-types-and-operations)
   - [Versioned content](#versioned-content)
     - [Version-specific-operations](#version-specific-operations)
     - [Version-specific arguments](#version-specific-arguments)
     - [Version-specific fields](#version-specific-fields)
   - [Define interfaces](#define-interfaces)
   - [Define input types](#define-input-types)
 - [Extending](#extending)
   - [Adding/removing fields from thirdparty code](#adding-removing-fields-from-thirdparty-code)
   - [Updating the core operations](#updating-the-core-operations)
   - [Adding new operations](#adding-new-operations)
   - [Changing behaviour with Middleware](#changing-behaviour-with-middleware)
 - [Testing/debugging queries and mutations](#testingdebugging-queries-and-mutations)
 - [TODO](#todo)

