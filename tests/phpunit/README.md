[PHPUnit][phpunit] provides the necessary environment to execute the in the subsequent directories provided unit tests together with the following base elements. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw-testing].

Tests can be by executed by either running the `mw-phpunit-runner.php` script or [`phpunit`][mw-testing] together with the PHPUnit configuration file found in the root directory.

```sh
php mw-phpunit-runner.php [options]
```

### Test types

- Unit test mostly used for testing technical specifications of a unit, module, or class.
- Integration test combines multiple components and verifies its interplay between those modules
- System test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units

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

### Fuseki integration

When running integration tests with [Jena Fuseki][fuseki] it is suggested that the `in-memory` option is used to avoid potential loss of production data during test execution.

```
fuseki-server --update --port=3030 --mem /db
```
```
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
```

## Miscellaneous
* [Using mocks during a test](mocks/README.md)
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)

[phpunit]: http://phpunit.de/manual/4.1/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[fuseki]: https://jena.apache.org/