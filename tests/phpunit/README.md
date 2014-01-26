[PHPUnit][phpunit] provides the necessary environment to execute the in the subsequent directories provided unit tests together with the following base elements. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw].

### TestTypes

#### Unit test
Testing technical specifications of a unit, module, or class.

#### Integration, regression and system test
An integration test combines multiple components and verifies its interplay between those modules while a system test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units.

### TestCases
* <code>MwIntegrationTestCase</code> is generally used to test integration with MediaWiki
* <code>MwRegressionTestCase</code> used to test the import of regression data set together with appropriate assert methods
* <code>SemanticMediaWikiTestCase</code> derives from <code>PHPUnit_Framework_TestCase</code> and adds some convenient functions
* <code>ApiTestCase</code> derives from SemanticMediaWikiTestCase and provides a framework for unit tests that directly require access to the MediaWiki Api interface
* <code>ParserTestCase</code> derives from SemanticMediaWikiTestCase
* <code>QueryPrinterTestCase</code> base class for all query printers
* <code>SpecialPageTestCase</code> derives from SemanticMediaWikiTestCase

### Miscellaneous
* [Using mocks during a test](mocks/README.md)
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)

[phpunit]: http://phpunit.de/manual/3.7/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing