
# Different types of testing

In general there are two different types of testing a manual (without using any tool or script) and/or an automated approach.

If you choose to do manual testing you just download a related branch using `composer require "mediawiki/semantic-media-wiki:dev-foo` where `foo` refers to the branch name and after the download run `composer dump-autoload` to ensure that all classes are correctly initialized before starting a test.

For the automated approach, Semantic MediaWiki uses [PHPUnit][phpunit] as scripted testing methodology. Scripted tests are used to verify that an expected behaviour occurs for specified requirements and enables to decide whether a result can be accepted or has to be rejected.

- Unit test in most cases refers to a test that confirms an expected result for a unit, module, or class
- Integration test normally combines multiple components into a single process to verify that the integration produces an expected result
- System test (and its individual modules) is treated as "black-box" in order to observe behaviour as a whole rather than its units

# PHPUnit

Most scripted tests for SMW are written for and executed by PHPUnit. PHPUnit provides the necessary environment to execute unit tests within PHP. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

## Running tests

In case PHUnit is not already installed, use `composer require phpunit/phpunit:~4.1` to install the package and execute the tests by simply running the `mw-phpunit-runner.php` script from the test directory or use [`phpunit`][mw-phpunit-testing] together with the PHPUnit configuration file and MediaWiki's `phpunit.php` loader.

```sh
php mw-phpunit-runner.php [options]
```

## Writing tests

Writing meaningful tests isn't easy nor is it complicated but it requires some diligence on how to setup a test and its environment. One simple rule is to avoid to use of hidden expectations or inheritance as remedy for the "less code is good code" aesthetics. Allow the code to be readable and if possible follow the [arrange, act, assert][aaa] pattern and yet again __"Don't do magic"__.

### Test cases

The use of `MediaWikiTestCase` is discouraged as its binds tests and the test environment to MediaWiki. Generally it is best to use `PHPUnit_Framework_TestCase` and in case where a MW database connection is required `MwDBaseUnitTestCase` should be used instead.

* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

### Test fixtures

When writing tests, it is suggested to use available [immutable fixtures][phpunit-fixtures] as baseline to create repeatable object instances during a test. Available fixtures can be found in `SMW\Tests\Fixtures\*`.

Another possibility is to use MediaWiki's XML format to import fixtures (in form of content pages), for more details see `SMW\Tests\Integration\MediaWiki\Import\*`.

## Integration tests

Integration tests are vital to confirm expected behaviour of a component from an integrative perspective that occurs through the interplay with its surroundings. `SMW\Tests\Integration\` contains most of the tests that target the validation of reciprocity with MediaWiki together with listed services such as:

- `FUSEKI`: Jena Fuskei 1.0.2 is integrated
- `VIRTUOSO`: Virtuoso-opensource-6.1 is integrated
- `FOURSTORE`: 4Store is installable but not executable due to [issue #110](https://github.com/garlik/4store/issues/110)

For details about the test environment see [SPARQLStore integration testing](../includes/src/SPARQLStore/README.md).

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
