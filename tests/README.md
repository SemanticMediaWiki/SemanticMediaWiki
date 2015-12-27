Running tests is commonly divided into a manual (without using any tool or automated script) and an automated approach.

# Manual testing

If you want to run some manual tests (either as scripted or exploratory test procedure) then you just have to:

1. Download a related branch using `composer require "mediawiki/semantic-media-wiki:dev-foo` (where `foo` refers to the branch name) or in case you want to test the current master, use `@dev` or `dev-master` as version together with `minimum-stability: dev` flag so that the branch/master can be fetched without any stability limitations.
2. Run `composer dump-autoload` to ensure that all registered classes are correctly initialized before starting any test procedure.

# Automated testing (PHPUnit)

For the automated approach, Semantic MediaWiki relies on [PHPUnit][phpunit] as scripted testing methodology. Scripted tests are used to verify that an expected behaviour occurs for codified requirements on whether a result can be accepted or has to be rejected for the given conditions.

- Unit test refers to a script that verifies results for a unit, module, or class against an expected outcome in an isolated environment
- Integration test (functional test) normally combines multiple components into a single process to verify results in a production like environment (DB access, sample data etc.)
- System test (and its individual modules) is treated as "black-box" to observe behaviour as a whole rather than its units

## Running tests

1. Verify that PHUnit is installed and in case it is not use `composer require phpunit/phpunit:~4.7 --update-with-dependencies` to add the package
2. Verify that your MediaWiki installation comes with its test files and folders (e.g. `/myMediawikiFolder/tests` ) in order for Semantic MediaWiki to have access to registered MW-core classes. If the `tests` folder is missing, you may download it from a matched [release source](https://github.com/wikimedia/mediawiki/releases).
3. Run `composer phpunit` from the Semantic MediaWiki base directory (e.g. `/extensions/SemanticMediaWiki`) using a standard command line tool which should output something like:

<pre>
$ composer phpunit
> php ../../tests/phpunit/phpunit.php -c phpunit.xml.dist

Semantic MediaWiki: 2.4-alpha (SMWSparqlStore, mysql, sesame)
MediaWiki:          1.25.4 (MediaWiki vendor autoloader)

Execution date:     2015-01-01 01:00

PHPUnit 4.7.7 by Sebastian Bergmann and contributors.

Runtime:        PHP 5.6.8
Configuration:  .../extensions/SemanticMediaWiki/phpunit.xml.dist

............................................................   60 / 3328 (  1%)
............................................................  120 / 3328 (  3%)
</pre>

Information about PHPUnit in connection with MediaWiki can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

## Writing tests

Writing meaningful tests isn't easy nor is it complicated but it requires some diligence on how to setup a test and its environment. One simple rule is to avoid to use of hidden expectations or inheritance as remedy for the "less code is good code" aesthetics. Allow the code to be readable and if possible follow the [arrange, act, assert][aaa] pattern and yet again __"Avoid doing magic"__.

For a short introduction on "How to write a test for Semantic MediaWiki", have a look at the [video](https://www.youtube.com/watch?v=v6JRfk5ZmsI).

### Test cases

The use of `MediaWikiTestCase` is discouraged as its binds tests and the test environment to MediaWiki. Generally it is best to use `PHPUnit_Framework_TestCase` and in case where a MW database connection is required `MwDBaseUnitTestCase` should be used instead.

* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

## Integration tests

Integration tests are vital to confirm expected behaviour of a component from an integrative perspective that occurs through the interplay with its surroundings. `SMW\Tests\Integration\` contains most of the tests that target the validation of reciprocity with MediaWiki and/or other services such as:

- `SPARQLStore` ( `fuseki`, `virtuoso`, `blazegraph`, or `sesame` )
- Extensions such as `SM`, `SESP`, `SBL` etc.

Some details about the integration test environment can be found [here](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/travis/README.md).

### Write integration tests using `json` script

Integration tests can be written in a pseudo `json` script in combination with a specialized `TestCaseRunner` that handles the necessary object setup and tear down process for each test execution.

The script like test definition was introduced to lower the barrier of understanding of what is being tested by using a wikitext notation (internally PHPUnit is used by the `ByJsonTestCaseProvider` to run/provide the actually test).

A new test file (with different test cases) is automatically added by a `TestCaseRunner` as soon as it is placed within the location expected by the runner.

The section `properties` and `subjects` contain object entities that are planned to be used during the test which are specified by a name and a content (generally the page content in wikitext).

<pre>
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
</pre>

The test result assertion is done in a very simplified way but expressive enough for users to understand the test objective and its expected results. For example, verifying that a result printer does output a certain string, one has to the define an expected output in terms of:

<pre>
"format-testcases": [
	{
		"about": "#0 this case is expected to output ...",
		"subject": "Example/Test/1",
		"expected-output": {
			"to-contain": [
				"abc",
				"123"
			]
		}
	}
}
</pre>

Test case definitions are available using specialized assertion methods with:

* `query-testcases`, `concept-testcases`, and `format-testcases`
* `parser-testcases`
* `rdf-testcases`
* `special-testcases`

It can happen that an output is mixed with message dependent content (which when changing the site/content language will make the test script fail) and therefore it is recommended to fix the settings the test is intended for to pass with something like:

<pre>
"settings": {
	"wgContLang": "en",
	"wgLang": "en",
	"smwgNamespacesWithSemanticLinks": {
		"NS_MAIN": true,
		"SMW_NS_PROPERTY": true
	}
}
</pre>

A complete list of available `json` test files can be found [here](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/ByJsonScript/README.md).

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
