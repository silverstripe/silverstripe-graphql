# Silverstripe CMS GraphQL Server 

[![CI](https://github.com/silverstripe/silverstripe-graphql/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-graphql/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

This modules serves Silverstripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
representations, with helpers to generate schemas based on Silverstripe model
introspection. It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library. The APIs are
very similar.

## This is the 4.x pre-release branch

The stable release of this module is on the default `3` branch, which contains documentation inlined into the README. Documentation for this release is on the [main documentation site](https://doc.silverstripe.org/en/4/developer_guides/graphql/).

## Installing on silverstripe/recipe-cms < 4.11

If your project uses silverstripe/recipe-cms, it is still locked to the stable release `silverstripe/graphql:^3`. To use `silverstripe/graphql:^4`, you'll need to "inline" the `silverstripe/recipe-cms` requirements in your root `composer.json` and change `silverstripe/graphql` to `^4`.

You can inline `silverstripe/recipe-cms` by running this command:

```
composer update-recipe silverstripe/recipe-cms
```

Alternatively, you can remove `silverstripe/recipe-cms` from your root `composer.json` and replace it with the the contents of the `composer.json` in `silverstripe/recipe-cms`.

## Documentation

See [doc.silverstripe.org](https://doc.silverstripe.org/en/4/developer_guides/graphql/).
