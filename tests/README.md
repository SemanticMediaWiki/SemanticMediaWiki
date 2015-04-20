Running tests is commonly divided into a manual (without using any tool or automated script) and an automated approach.

# Manual testing

If you want to run some manual tests (either as scripted or exploratory test procedure) then you just have to:

1. Download a related branch using `composer require "mediawiki/semantic-media-wiki:dev-foo` (where `foo` refers to the branch name) or in case you want to test the current master, use `@dev` or `dev-master` as version together with `minimum-stability: dev` flag so that the branch/master can be fetched without any stability limitations.
2. Run `composer dump-autoload` to ensure that all registered classes are correctly initialized before starting any test procedure.

# Automated testing (PHPUnit)

For the automated approach, Semantic MediaWiki relies on [PHPUnit][phpunit] as scripted testing methodology. Scripted tests are used to verify that an expected behaviour occurs for specified requirements whether a result can be accepted or has to be rejected for the codified boundaries.

- Unit test in most cases refers to a test that tests an expected result for a unit, module, or class
- Integration test normally combines multiple components into a single process to verify that an expected result is produced
- System test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units

## Running tests

1. Verify that PHUnit is installed and in case it is not use `composer require phpunit/phpunit:~4.3` to add the package
2. Use `composer phpunit` from the extension base directory, or [`phpunit`][mw-phpunit-testing] directly in connection with the PHPUnit `XML` configuration file (together with MediaWiki's `phpunit.php` loader otherwise required MediaWiki classes are not loaded).

Information about PHPUnit in connection with MediaWiki can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

## Writing tests

Writing meaningful tests isn't easy nor is it complicated but it requires some diligence on how to setup a test and its environment. One simple rule is to avoid to use of hidden expectations or inheritance as remedy for the "less code is good code" aesthetics. Allow the code to be readable and if possible follow the [arrange, act, assert][aaa] pattern and yet again __"Avoid doing magic"__.

### Test cases

The use of `MediaWikiTestCase` is discouraged as its binds tests and the test environment to MediaWiki. Generally it is best to use `PHPUnit_Framework_TestCase` and in case where a MW database connection is required `MwDBaseUnitTestCase` should be used instead.

* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

### Test fixtures

When writing tests, it is suggested to use available [immutable fixtures][phpunit-fixtures] as baseline to create repeatable object instances during a test. Available fixtures can be found in `SMW\Tests\Utils\Fixtures\*`.

Another possibility is to use MediaWiki's XML format to import fixtures (in form of content pages), for more details see `SMW\Tests\Integration\MediaWiki\Import\*`.

## Integration tests

Integration tests are vital to confirm expected behaviour of a component from an integrative perspective that occurs through the interplay with its surroundings. `SMW\Tests\Integration\` contains most of the tests that target the validation of reciprocity with MediaWiki together with listed services such as:

- `SPARQLStore` ( `fuseki`, `virtuoso`, or `4store` )
- Other extensions that require SMW ( `SM`, `SESP`, `SBL` etc.)

For details about the test environment see [integration testing](../includes/build/travis/README.md).

### Write integration tests using `json` script

Integration tests can be written in a pseudo `json` script in connection with a `TestCaseRunner` that handles the necessary object setup and tear down process for each test execution. The script was introduced to increase the understanding of what is being tested by using a script close to the wikitext notation (internally PHPUnit is used by the `ByJsonTestCaseProvider` to run/provide the actually test).

A new test file (with different test cases) is automatically loaded and run by a `TestCaseRunner` as soon as it is placed in a location specified by the runner.

Each `TestCaseRunner` contains a different interpretation of the `json` script to keep the format straightforward but still allows for individual test assertions. Currently the following `TestCaseRunner` are provided:

- `ByJsonRdfTestCaseRunnerTest` for rdf output assertion
- `ByJsonQueryTestCaseRunnerTest` to verify formats, queries, and concepts
- `ByJsonParserTestCaseRunnerTest` to check for  parser and store specific data

The section `properties` and `subjects` contain object entities that are planned to be used during the test which are specified by a name and a content (generally the page content in wikitext).
```json
"properties": [
	{
		"name": "Has description",
		"contents": "[[Has type::Text]]"
	}
],
"subjects": [
	{
		"name": "Page that contains text",
		"contents": "[[Has description::Foo]]"
	},
	{
		"name": "Another page that contains text",
		"namespace": "NS_HELP",
		"contents": "[[Has description::Bar]]"
	}
]
```

The test result assertion is done in a very simplified way but expressive enough for users to understand on what is being tested. For example, verifying that a result printer does output a certain string, one has to the define an expected output in terms of:

```json
"expected-output": {
	"to-contain": [
		"<table class=\"sortable wikitable smwtable\">"
	]
}
```
It can happen that an output is mixed with message dependent content (which when changing the site/content language will make the test script fail) and therefore it is recommended to fix the settings the test is intended for to pass with something like:
```json
"settings": {
	"wgContLang": "en",
	"wgLang": "en",
	"smwgNamespacesWithSemanticLinks": {
		"NS_MAIN": true,
		"SMW_NS_PROPERTY": true
	}
}
```

For other assertion options it is best to look at existing `json` test files (e.g [rdf-001.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/Rdf/rdf-001.json) or [parser-001-restricted-property-use.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/Parser/parser-001-restricted-property-use.json)).

## Benchmark tests

For details, please have a look at the [benchmark guide](phpunit/Benchmark/README.md) document.

# JavaScript (QUnit)

Running qunit tests in connection with MediaWiki requires to execute [Special:JavaScriptTest][mw-qunit-testing]. QUnit tests are currently not executed on Travis (see [#136][issue-136]).

# Miscellaneous
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)
* [Test Doubles](http://www.martinfowler.com/bliki/TestDouble.html) (mocks, stubs etc.) and [how to write them](http://phpunit.de/manual/4.1/en/test-doubles.html)

[phpunit]: http://phpunit.de/manual/4.1/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw-phpunit-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[mw-qunit-testing]: https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing
[issue-136]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/136
[phpunit-fixtures]: http://phpunit.de/manual/current/en/fixtures.html
[aaa]: http://c2.com/cgi/wiki?ArrangeActAssert
