[PHPUnit][phpunit] provides the necessary environment to execute the in the subsequent directories provided unit tests together with the following base elements. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw].

### Test types

- Unit test mostly used for testing technical specifications of a unit, module, or class.
- Integration test combines multiple components and verifies its interplay between those modules
- System test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units

### Test cases

We discourage the usage of `MediaWikiTestCase` as this binds the test environment setup and execution to tight to MediaWiki. Generally it is best to use `PHPUnit_Framework_TestCase` and in case a MW database connection is required it is suggested to use `SMW\Tests\MwDBaseUnitTestCase` instead.

* `MwIntegrationTestCase` used for testing MediaWiki integration
* `MwRegressionTestCase` used for regression testing together with XML data import
* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

### Obsolete
* `ParserTestCase` derives from SemanticMediaWikiTestCase
* `SemanticMediaWikiTestCase` derives from <code>PHPUnit_Framework_TestCase</code> and adds some convenient functions
* `ApiTestCase` derives from `SemanticMediaWikiTestCase` and provides a framework for unit tests that directly require access to the MediaWiki Api interface

### Miscellaneous
* [Using mocks during a test](mocks/README.md)
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)

[phpunit]: http://phpunit.de/manual/3.7/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing