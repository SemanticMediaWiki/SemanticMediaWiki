Tests are commonly divided into a manual (without using any tool or automated
script) and an automated scripted test approach.

# Manual testing

If you want to run some manual tests (either as scripted or exploratory test procedure) then you just have to:

1. Download a related branch using `composer require "mediawiki/semantic-media-wiki:dev-foo` (where `foo` refers to the branch name) or in case you want to test the current master, use `@dev` or `dev-master` as version together with the `minimum-stability: dev` flag so that the branch/master can be fetched without any stability limitations.
2. Run `composer dump-autoload` to ensure that all registered classes are correctly initialized before starting any test procedure.

# Automated testing (PHPUnit)

For the automated approach, Semantic MediaWiki relies on [PHPUnit][phpunit] as scripted testing methodology. Scripted tests are used to verify that an expected behaviour occurs for codified requirements on the given conditions.

- Unit test refers to a script that verifies results for a unit, module, or class against an expected outcome in an isolated environment
- Integration test (or functional test) normally combines multiple components into a single process and verifies the results in a semi-production like environment (including DB access, sample data etc.)
- System test (and its individual modules) is treated as "black-box" to observe behaviour as a whole rather than its units

## Running tests

1. Verify that PHUnit is installed in `myMediawikiFolder/vendor/phpunit`. In case it is not installed, use `composer require phpunit/phpunit:~6.5 --update-with-dependencies` to add the package
2. Verify that your MediaWiki installation comes with its test files and folders (e.g. `/myMediawikiFolder/tests` ) in order for Semantic MediaWiki to have access to registered MW-core classes. If the `tests` folder is missing then you may follow the [release source](https://github.com/wikimedia/mediawiki/releases) to download the missing files.
3. Run `composer phpunit` from the Semantic MediaWiki base directory (e.g. `myMediawikiFolder/extensions/SemanticMediaWiki`) using a standard command line tool which should output something like:

<pre>
$ composer phpunit

Using PHP 7.2.30

Semantic MediaWiki: 3.2.0-alpha, git: abc234b
                    SMWSQLStore (postgres:9.5.10)

MediaWiki:          1.31.7, git: 42e0b35 (refs/heads/REL1_31)
                    Extension vendor autoloader

Site language:      en
Execution time:     2020-04-25 06:53

Debug logs:         Disabled
Xdebug:             Disabled (or not installed)

Intl/ICU:           1.1.0 / 52.1
PCRE:               8.41 2017-07-05

PHPUnit 6.5.14 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.2.30
Configuration: /home/travis/build/SemanticMediaWiki/mw/extensions/SemanticMediaWiki/phpunit.xml.dist

.............................................................   61 / 8526 (  0%)
.............................................................  122 / 8526 (  1%)
</pre>

Information about PHPUnit in connection with MediaWiki can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

## Writing tests

Writing meaningful tests isn't difficult but requires some diligence on how to setup a test and its environment. One simple rule is to avoid the use of hidden expectations or inheritance as remedy for the "less code is good code" aesthetics. Allow the code to be readable and if possible follow the [arrange, act, assert][aaa] pattern.

For a short introduction on "How to write a test for Semantic MediaWiki", have a look at [this](https://www.youtube.com/watch?v=v6JRfk5ZmsI) video.

<pre>
/tests
├─ /phpunit
│	├─ Benchmark
│	├─ Fixtures
│	├─ Integration
│	│	├─ ...
│	│	└─ JSONScript
│	├─ Structure
│	└─ Unit
│
└─ /qunit
</pre>

- `Benchmark` contains collections of tests running benchmarks
- `Fixtures` contains fixed data and schemata
- `Integration` contains tests classified as testing the integration with MediaWiki, rely on an active DB connection, or connect to any other external service
- `Structure` contains tests that verify some structural components, or not directly test a specific integration with Semantic MediaWiki or MediaWiki
- `Unit` contains unit tests (those tests should not rely on an enabled MediaWiki or DB connection)

### Unit tests

The use of `MediaWikiTestCase` is discouraged (as its binds tests and the test environment to MediaWiki) and it is best to rely on `PHPUnit_Framework_TestCase` and where a MW database connection is required, use the `MwDBaseUnitTestCase` instead.

* `QueryPrinterTestCase` base class for all query and result printers
* `SpecialPageTestCase` derives from `SemanticMediaWikiTestCase`

### Integration tests

Integration tests are vital to confirm the behaviour of a component from an integrative perspective that occurs through an interplay with its surroundings.

Those tests don't replace unit tests, they complement them to verify that an expected outcome does actually occur in combination with MediaWiki and other services.

Integration tests can help reduce the recurrence of regressions or bugs, given that a developers follows a simple process:

- Make a conjecture or hypothesis about the cause of the bug or regression
- Find a minimal test case (using wiki text at this point should make it much easier to replicate a deviated behaviour)
- Write a `JSON` test and have it __fail__
- Apply a fix
- Run the test again and then run all other integration tests to ensure nothing else was altered by accidentally introducing another regression not directly related to the one that has been fixed

The `Integration` directory is expected to host tests that target the validation of reciprocity with MediaWiki and/or other services such as:

- Triple-stores (necessary for the `SPARQLStore`)
- Extensions (`SESP`, `SBL` etc.)

Some details about the integration test environment can be found [here](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/travis/README.md).

#### JSONScript

One best practice in Semantic MediaWiki is to write integration tests as pseudo [`JSONScript`][JSONScript] to allow non-developers to review and understand the setup and requirements of its test scenarios.

The `JSON` format was introduced as abstraction layer to lower the barrier of understanding of what is being tested by using the wikitext markup to help design test cases quicker without the need to learn how `PHPUnit` or internal `MediaWiki` objects work.

## Benchmark tests

For details, please have a look at the [benchmark guide](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Benchmark/README.md) document.

# JavaScript (QUnit)

Running qunit tests in connection with MediaWiki requires to execute
[Special:JavaScriptTest][mw-qunit-testing]. QUnit tests are currently not
executed on Travis (see [#136][issue-136]).

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
[JSONScript]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/README.md
