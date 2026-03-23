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

Information about PHPUnit in connection with MediaWiki can be found at [smw.org][smw] and [mediawiki.org][mw-phpunit-testing].

## Running tests

See [docs/DEVELOPMENT.md](../docs/DEVELOPMENT.md) for full setup instructions. All test commands run inside Docker.

### Running all tests

```sh
make composer-test
```

This runs lint, PHPCS, and all PHPUnit test suites.

### Running a single testsuite

Test suites are defined in `phpunit.xml.dist`:

* `semantic-mediawiki-check`
* `semantic-mediawiki-unit`
* `semantic-mediawiki-integration`
* `semantic-mediawiki-import`
* `semantic-mediawiki-structure`
* `semantic-mediawiki-benchmark`

To run a single testsuite:

```sh
make composer-test COMPOSER_PARAMS="-- --testsuite=semantic-mediawiki-unit"
```

### Running a single test

```sh
make composer-test COMPOSER_PARAMS="-- --filter ParserAfterTidyTest"
```

## Writing tests

Writing meaningful tests isn't difficult but requires some diligence on how to setup a test and its environment. One simple rule is to avoid the use of hidden expectations or inheritance as remedy for the "less code is good code" aesthetics. Allow the code to be readable and if possible follow the [arrange, act, assert][aaa] pattern.

For a short introduction on "How to write a test for Semantic MediaWiki", have a look at [this](https://www.youtube.com/watch?v=v6JRfk5ZmsI) video.

```
/tests
├─ /phpunit
│	├─ Benchmark         # Performance benchmarks
│	├─ Fixtures          # Fixed data, schemata, and test helpers
│	├─ Integration       # Integration tests (require MW, DB, or external services)
│	│	├─ JSONScript    # JSON-based declarative integration tests
│	│	├─ MediaWiki     # MW hook and API integration tests
│	│	└─ ...
│	├─ Structure         # Structural/sanity checks
│	├─ Unit              # Unit tests (no MW or DB dependency)
│	│	├─ DataValues    # Mirrors src/DataValues/
│	│	├─ MediaWiki     # Mirrors src/MediaWiki/
│	│	├─ SQLStore      # Mirrors src/SQLStore/
│	│	└─ ...           # Subdirectories mirror the source tree
│	└─ Utils             # Shared test utilities and validators
│
└─ /qunit                # JavaScript (QUnit) tests
```

### Unit tests

Unit tests live in `tests/phpunit/Unit/` and extend `PHPUnit\Framework\TestCase`. They should not rely on MediaWiki services, database connections, or external services. All dependencies should be mocked.

The directory structure inside `Unit/` mirrors the source tree (`src/`). For example, unit tests for `src/SQLStore/RedirectStore.php` go in `Unit/SQLStore/RedirectStoreTest.php`.

Base classes available:
* `QueryPrinterTestCase` — base class for all query and result printers
* `SpecialPageTestCase` — derives from `SemanticMediaWikiTestCase`

### Integration tests

Integration tests live in `tests/phpunit/Integration/` and typically extend `SMWIntegrationTestCase` (which extends MediaWiki's `MediaWikiIntegrationTestCase`). They test interaction with MediaWiki, database, or external services.

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

#### JSONScript

One best practice in Semantic MediaWiki is to write integration tests as pseudo [`JSONScript`][JSONScript] to allow non-developers to review and understand the setup and requirements of its test scenarios.

The `JSON` format was introduced as abstraction layer to lower the barrier of understanding of what is being tested by using the wikitext markup to help design test cases quicker without the need to learn how `PHPUnit` or internal `MediaWiki` objects work.

## Benchmark tests

For details, please have a look at the [benchmark guide](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Benchmark/README.md) document.

# JavaScript (QUnit)

Running qunit tests in connection with MediaWiki requires to execute
[Special:JavaScriptTest][mw-qunit-testing].

# Miscellaneous
* [Writing testable code](https://semantic-mediawiki.org/wiki/Help:Writing_testable_code)
* [Code coverage in a nutshell](https://semantic-mediawiki.org/wiki/Help:Code_coverage_in_a_nutshell)
* [Test Doubles](http://www.martinfowler.com/bliki/TestDouble.html) (mocks, stubs etc.) and [how to write them](https://docs.phpunit.de/en/9.6/test-doubles.html)

[phpunit]: https://docs.phpunit.de/en/9.6/
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw-phpunit-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[mw-qunit-testing]: https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing
[aaa]: http://c2.com/cgi/wiki?ArrangeActAssert
[JSONScript]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/README.md
