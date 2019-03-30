# Hacking Semantic MediaWiki

This document should help newcomers and developers to navigate around Semantic MediaWiki and its development environment.

The main objective of the `Semantic MediaWiki` software is to provide "semantic" functions on top of MediaWiki to enable machine-reading of wiki-content and allow structured content to be queried by means of different backends the software supports including:

- SQL (see [SQLStore](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/README.md))
- Elasticsearch (see [ElasticStore](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/README.md))
- SPARQL (see [SPARQLStore](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SPARQLStore/README.md))

## Getting started ...

- Read this document
- Install [git](https://www.semantic-mediawiki.org/wiki/Help:Using_Git)
- Install/clone `Semantic MediaWiki` with `@dev` (with development happening against the master branch)
- Run `composer test` locally (see the test section) and verify that your installation and test environment are setup correctly so that you would find something like "_OK, but incomplete, skipped, or risky tests! Tests ..._" at the end as output (after the test run)
- Send a PR to the [`Semantic MediaWiki`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/) repository to verify that your git works and you are able to replicate changes against the master branch
- Get yourself familiar with the [Travis-CI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki) and observe how a PR triggers CI jobs and review the output of those jobs (important when a job doesn't pass and you need to find the cause for a failure)
- You encountered some problems, create a [ticket](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/new)

## Development

### Policy

The general policy of the `Semantic MediaWiki` software and the development thereof is:

- No MediaWiki tables are modified or altered, any data that needs to be stored persistently is done using its own tables (writing to the cache is an exception)
- No MediaWiki classes are modified, patched, or otherwise changed
- Semantic MediaWiki tries to depend only on a selected pool of MediaWiki core classes (`Title`, `Wikipage`, `ParserOutput`, `Revision`, `Language` ... ) to minimize the potential for breakage during release changes
- Use publicly available `Hooks` and `API` interfaces to extend MediaWiki with Semantic MediaWiki functions
- Object interaction with MediaWiki objects should be done using accessors in the `SMW\MediaWiki` namespace

### Conventions

Some simple rules that developers and the project tries to follow (of course there are exceptions or legacy cruft) is to create testable components where  classes have a smaller footprint and come with a dedicated responsibility.

- A `class` has a defined responsibility and boundary
- Dependency injection goes before inheritance, meaning that all objects used in a class should be injected.
- Instance creation (e.g. `new Foo( ... )`) is delegated to a factory service
- A factory service should avoid using conditionals (`if ... then ...`) to create an instance
- Instance creation and dependency injection are done using a service locator or dependency builder
- The top-level namespace is `SMW` and each component should be placed in a namespace that represents the main responsibility of the component
- [`PSR-4`](https://www.php-fig.org/psr/psr-4/) is used for resolving classes and namespaces in the `src` directory (`includes` is the legacy folder that doesn't necessarily follow any of the policies or conventions mentioned in this document)
- Development happens against the master branch (see also the [release process](https://www.semantic-mediawiki.org/wiki/Release_process)) and will be release according the the available release plan, backports should be cherry-picked and merged into the targeted branch
- Using [`type hinting`](http://php.net/manual/en/language.oop5.typehinting.php) consistently throughout a repository is vital to ensure class contracts can be appropriately followed
- Trying to follow [`Single responsibility principle`](https://en.wikipedia.org/wiki/Single_responsibility_principle) and applying [`inversion of control`](https://en.wikipedia.org/wiki/Inversion_of_control) (i.e dependency injection, factory pattern, service locator pattern) is a best practice approach

### Testing

The `SemanticMediaWiki` software alone deploys ~7000 tests (as of 16 March 2019) that are required to be passed before changes can be merged into the repository and are commonly divided into unit and integration tests.

- Read the [introduction](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/README.md) to the Semantic MediaWiki test environment and how to use `PHPUnit` and how to write `JSONScript` integration tests
- It is expected that each new class is covered by unit test and if the functionality spans into different components integration tests are provided as well to ensure the behaviour sought is actually observable and builds the base to define the behavioural boundaries.

#### Continues integration (CI)

The project uses [Travis-CI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki) to run its tests on different platforms with different services enabled to provide a wide range of  environments including MySQL, SQLite, and Postgres.

- [`.travis.yml`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/.travis.yml) testing matrix
- Settings and [configurations](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/travis/README.md) to tune the Travis-CI setup

## Architecture guide

- [`Datamodel`][datamodel] contains the most essential architectural choice of Semantic MediaWiki for the management of its data including [`DataItem`][dataitem], [`SemanticData`][semanticdata], [`DataValue`][datavalue], and [`DataTypes`][datatype]

## Hacking by examples

- [Creating annotations and storing data](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/storing.annotations.md)
- [Querying data](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/querying.data.md)
- [Writing a result printer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/writing.resultprinter.md)
- [Developing an extension](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/developing.extension.md)
- [Register a custom datatype][datatype]
- [Extending consistency checks on a property page](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.declarationexaminer.md)
- [Extending property annotators](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.propertyannotator.md) for a core predefined property, also the [Semantic Extra Special Properties](https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties) extension provides a development space for deploying other predefined (special) property annotations
- [Extending constraints and checks](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md)

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
