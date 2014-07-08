
Tests are developed and used to verify that an expected behaviour does occur within specified boundaries where a set of parameters define the requirements in which results are accepted or rejected.

- Unit test mostly used for testing technical specifications of a unit, module, or class.
- Integration test combines multiple components and verifies its interplay between those modules
- System test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units

# PHPUnit

[PHPUnit][phpunit] provides the necessary environment to execute unit tests within PHP. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

Tests can be executed by either running the `mw-phpunit-runner.php` script or [`phpunit`][mw-phpunit-testing] together with the PHPUnit configuration file found in the root directory.

```sh
php mw-phpunit-runner.php [options]
```

### Test cases

The use of `MediaWikiTestCase` is discouraged as its binds tests and the test environment to MediaWiki. Generally it is best to use `PHPUnit_Framework_TestCase` and in case where a MW database connection is required `MwDBaseUnitTestCase` should be used instead.

* `MwDBSQLStoreIntegrationTestCase` specifically used for testing MediaWiki/SQLStore/DB integration
* `MwRegressionTestCase` used for regression testing together with XML data import
* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

### Obsolete
* `ParserTestCase` derives from SemanticMediaWikiTestCase
* `SemanticMediaWikiTestCase` derives from <code>PHPUnit_Framework_TestCase</code> and adds some convenient functions
* `ApiTestCase` derives from `SemanticMediaWikiTestCase` and provides a framework for unit tests that directly require access to the MediaWiki Api interface

## Integration testing

Additional services can be enabled on Travis-CI to expand the test environment, available at present:

- `FUSEKI`: Jena Fuskei 1.0.2 is integrated
- `VIRTUOSO`: Virtuoso-opensource-6.1 is integrated
- `FOURSTORE`: 4Store is installable but not executable due to [issue #110](https://github.com/garlik/4store/issues/110)

### Jena Fuseki integration

When running integration tests with [Jena Fuseki][fuseki] it is suggested that the `in-memory` option is used to avoid potential loss of production data during test execution.

```sh
fuseki-server --update --port=3030 --mem /db
```
```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
```

Fuseki supports [TDB Dynamic Datasets][fuseki-dataset] (in SPARQL known as [RDF dataset][sparql-dataset]) which are currently not considered for testing but can be enabled using the following settings.

```sh
fuseki-server --update --port=3030 --memTDB --set tdb:unionDefaultGraph=true /db
```
```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myFusekiGraph';
```
### Virtuoso integration

Virtuoso-opensource 6.1

```sh
sudo apt-get install virtuoso-opensource
```

```php
$smwgSparqlDatabaseConnector = 'Virtuoso';
$smwgSparqlQueryEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlUpdateEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myVirtuosoGraph';
```

### 4Store integration

Currently, Travis-CI doesn't support `4Store` (1.1.4-2) as service but the following configuration has been sucessfully tested with the available test suite.

```sh
apt-get install 4store
```

```php
$smwgSparqlDatabaseConnector = '4store';
$smwgSparqlQueryEndpoint = 'http://localhost:8088/sparql/';
$smwgSparqlUpdateEndpoint = 'http://localhost:8088/update/';
$smwgSparqlDataEndpoint = 'http://localhost:8088/data/';
$smwgSparqlDefaultGraph = 'http://example.org/myFourstoreGraph';
```

# QUnit

Running qunit tests in connection with MediaWiki requires to execute [Special:JavaScriptTest][mw-qunit-testing]. QUnit tests are currently not executed on Travis (see [#136][issue-136]).

# Miscellaneous
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)
* [Test Doubles](http://www.martinfowler.com/bliki/TestDouble.html)(mocks, stubs etc.) and [how to write them](http://phpunit.de/manual/4.1/en/test-doubles.html)

[phpunit]: http://phpunit.de/manual/4.1/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw-phpunit-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[mw-qunit-testing]: https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing
[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
[issue-136]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/136
