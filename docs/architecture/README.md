[Development policies and practices](#development-policies-and-practices) | [Architecture guide](#architecture-guide) | [Technical insights](#technical-insights) | [Testing](#testing) | [Pull request](#create-a-pull-request)

## Objective

This document should help newcomers and developers to navigate around Semantic MediaWiki and its development environment.

The main objective of the `Semantic MediaWiki` software is to provide "semantic" functions on top of MediaWiki to enable machine-reading of wiki-content and allow structured content to be queried and displayed by means of employing different backends including:

- [`SQLStore`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/README.md) to be used as default storage and query engine for small and mid-size wikis
- [`ElasticStore`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/README.md) recommended to large wiki farms which need to scale or for users with a requirement to combine structured and unstructured searches
- [`SPARQLStore`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SPARQLStore/README.md) for advanced users that have an extended requirement to work with a triple store and linked data

## Development policies and practices

### Polices

The general policy of the `Semantic MediaWiki` software and the development thereof is:

- No MediaWiki tables are modified or altered, any data that needs to be stored persistently is relying on the Semantic MediaWiki's own [`database schema`][db-schema] (writing to the cache is an exception)
- No MediaWiki classes are modified, patched, or otherwise changed
- Only publicly available `Hooks` and `API` interfaces are used to extend MediaWiki with Semantic MediaWiki functions
- Classes and public methods (i.e. those declared using the `public` visibility attribute) marked as `@private` are not considered for public consumption or part of the public API hence a user should not rely upon these to be available as they may change their signature anytime or removed without prio notice
- Tables created and managed by Semantic MediaWiki should not be accessed directly, instead a user (or extension) should make use of the public available API to fetch relevant information

### Conventions

Some conventions to help developers and the project to maintain a consistent product and helps to create testable components where classes have a smaller footprint and come with a dedicated responsibility.

- The top-level namespace is `SMW` and each component should be placed in a namespace that represents the main responsibility of the component
- [`PSR-4`](https://www.php-fig.org/psr/psr-4/) is used for resolving classes and namespaces in the `src` directory (`includes` is the legacy folder that doesn't necessarily follow any of the policies or conventions mentioned in this document)
- Development happens against the master branch (see also the [release process](https://www.semantic-mediawiki.org/wiki/Release_process)) and will be release according the the available release plan, backports should be cherry-picked and merged into the targeted branch
- Semantic MediaWiki tries to depend only on a selected pool of MediaWiki core classes (`Title`, `Wikipage`, `ParserOutput`, `RevisionRecord`, `Language` ... ) to minimize the potential for breakage during release changes
- It is expected that each new class and functionality is covered by corresponding unit tests and if the functionality spans into different components integration tests are required as well to ensure that the behaviour is tested across components and produces deterministic and observable outputs.

#### Best practices

- A `class` has a defined responsibility and boundary
- Dependency injection goes before inheritance, meaning that all objects used in a class should be injected.
- Instance creation (e.g. `new Foo( ... )`) is delegated to a factory service
- Object interaction with MediaWiki objects should be done using accessors in the `SMW\MediaWiki` namespace
- A factory service should avoid using conditionals (`if ... then ...`) to create an instance
- Instance creation and dependency injection are done using a service locator or dependency builder
- Using [`type hinting`](http://php.net/manual/en/language.oop5.typehinting.php) consistently throughout a repository is vital to ensure class contracts can be appropriately followed
- Trying to follow [`Single responsibility principle`](https://en.wikipedia.org/wiki/Single_responsibility_principle) and applying [`inversion of control`](https://en.wikipedia.org/wiki/Inversion_of_control) (i.e dependency injection, factory pattern, service locator pattern) is a best practice approach
- Newly added functionality is expected to be accompanied by unit and integration test to ensure that its operation is verifiable and doesn't interfere with existing services
- Newly introduced features (or enhancements) that alter existing behaviour need to be guarded by a behaviour switch (or flag) allowing to restore any previous behaviour and need to be accompanied by [integration tests](#testing)
- To improve the readability of classes in terms of what is public and what are internals (not to be exposed outside of the class boundary), class methods are ordered by its visibility where `public` comes before `protected` which comes before `private` defined functions

## Architecture guide

- [Datamodel][datamodel] contains the most essential architectural choice of Semantic MediaWiki for the management of its data including:
  - [DataItem][dataitem]
  - [SemanticData][semanticdata]
  - [DataValue][datavalue]
  - [DataTypes][datatype]
- [Database schema][db-schema] and table definitions in Semantic MediaWiki
- [Glossary][glossary]

## Technical insights

- Creating [annotations and storing data](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/storing.annotations.md)
- Querying and displaying [data](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/querying.data.md)
- Writing a [result printer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/writing.resultprinter.md)
- Register a custom [datatype][datatype] or [predefined property][hook.property.initproperties.md]
- Extending [consistency checks](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.declarationexaminer.md) on a property page
- Extending [property annotators](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.propertyannotator.md) for core predefined properties, see also the [Semantic Extra Special Properties](https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties) extension that provides a development space for deploying other predefined (special) properties
- [Extending constraints](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md) and their checks
- Working with and [changing the table schema](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/changing.tableschema.md) of Semantic MediaWiki
- Managing [hook events][hooks], best practices for [developing an extension](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/developing.extension.md), and a [list of hooks][hook-list] provided by Semantic MediaWiki to extend its core functionality

## Testing

The `Semantic MediaWiki` software alone deploys ~7400 tests (as of July 2019) which are __required to pass__ before changes can be merged into the repository.

Tests are commonly divided into [unit][glossary] and [integration tests][glossary] where unit tests represent an isolated unit (or component) to be tested and normally doesn't require a database or other repository connection (e.g. triple store etc.). Integration tests on the other hand provide the means to test the interplay with other components by directly interacting with MediaWiki and its services. For example, about 80% of the CI running time is spend on executing integration tests as they normally run a full integration cycle (parsing, storing, reading, HTML generating etc.).

For an introduction on "How to use `PHPUnit`" and "How to write integration tests using `JSONScript`" see the relevant section in this [document](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/README.md).

### Continuous integration (CI)

The project uses [Travis-CI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki) to run its tests on different platforms with different services enabled to provide a wide range of  environments including MySQL, SQLite, and Postgres.

- [`.travis.yml`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/.travis.yml) testing matrix
- Settings and [configurations](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/travis/README.md) to tune the Travis-CI setup

## Create a pull request

Before creating a pull request it is recommended to:

- Read this document
- Install [git](https://www.semantic-mediawiki.org/wiki/Help:Using_Git)
- Install/clone `Semantic MediaWiki` with `@dev` (with development happening against the master branch)
- Run `composer test` locally (see the test section) and verify that your installation and test environment are setup correctly

### First PR

- Send a PR with subject [first pr] to the [`Semantic MediaWiki`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/) repository and verify that your git setup works and you are able to replicate changes against the master branch
- Get yourself familiar with the [Travis-CI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki) environment and observe how a PR triggers CI jobs and review the output of those jobs (important when a job doesn't pass and you need to find the cause for a failure)

### Preparing a PR

- Create a PR with your changes and send it to the `Semantic MediaWiki` repository
- Observe whether tests are failing or not, and when there are failing identify what caused them to fail
- In case your PR went green without violating any existing tests, go back to your original PR and add tests that covers the newly introduced behaviour (see the difference for unit and integration tests)
- Rebase and re-post your PR with the newly added tests and verify that they pass on all voting [CI](#testing) jobs

In an event that you encountered a problem, ask or create an [issue](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/new).

## See also

- https://doc.semantic-mediawiki.org/
- [Developer manual](https://www.semantic-mediawiki.org/wiki/Help:Developer_manual)
- [Programmer's guide](https://www.semantic-mediawiki.org/wiki/Help:Programmer%27s_guide)
- [Coding conventions](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/coding.conventions.md)

[datamodel]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.md
[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md
[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
[db-schema]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/database.schema.md
[hooks]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/managing.hooks.md
[hook.property.initproperties.md]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.property.initproperties.md
[hook-list]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks.md
[glossary]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/glossary.md
