## Extension usage

Extensions that want to create their own `JSONScript` integration tests and have them run against MediaWiki and Semantic MediaWiki can take advantage of the existing SMW test infrastructure for integration tests (script interpreter, assertions validators etc.) by:

- Extending the PHPUnit `bootstrap.php`
- Create a [test case][testcase] that extends from `LightweightJsonTestCaseScriptRunner`

### Extending the bootstrap

To ensure that relevant classes are registered and available during the test run add the following lines to the PHPUnit `bootstrap.php`.

```php
if ( !defined( 'SMW_PHPUNIT_AUTOLOADER_FILE' ) || !is_readable( SMW_PHPUNIT_AUTOLOADER_FILE ) ) {
	die( "\nThe Semantic MediaWiki test autoloader is not available" );
}

// Obligatory output to inform users about the extension/version used
print sprintf( "\n%-20s%s\n", "MY EXTENSION NAME", MY_EXTENSION_VERSION );

// Load the autoloader file
$autoloader = require SMW_PHPUNIT_AUTOLOADER_FILE;

// Use the autoloader to extend class maps etc.
$autoloader->addPsr4( ... );
```

### Create a test case

Semantic MediaWiki provides two script runners that can be used by extensions:

- `LightweightJsonTestCaseScriptRunner` allows to use the `parser`, `parser-html`, `special`, and `semantic-data` type assertions
- `ExtendedJsonTestCaseScriptRunner` provides additional assertion methods
- `JsonTestCaseScriptRunner` is the base runner that provides all methods necessary to run test cases, it also includes version checks as well as to validate custom defined dependencies

The `LightweightJsonTestCaseScriptRunner` was introduced to help users to quickly create a custom script runner (e.g. `CustomJsonScriptTest`) that iterates over the selected test location without much modification to the test itself besides adding the location of the test case folder.

#### Example

```php
namespace Foo\Tests\Integration;

use SMW\Tests\LightweightJsonTestCaseScriptRunner;

/**
 * @since ...
 */
class CustomJsonScriptTest extends LightweightJsonTestCaseScriptRunner {

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

}
```

### Create integration scenarios

The [bootstrap.json][bootstrap.json] contains an example that can be used as starting point for a test scenario. The [design][design.md] document holds  detail options and usage of assertions methods.

## Augment the JSON script

In some cases the selected `JSON` style may vary or contains information that require additional validation therefore the script runner can easily be extended with something like:

### JSON

```json
{
	"description": " ... ",
	"setup": [
		{
			"page": "Example/Bootstrap",
			"contents": "[[Has example::Example123]]"
		}
	],
	"tests": [
		{
			"type": "myType",
			"about": "...",
			"subject": "Example/Bootstrap",
			"assert-myType": {}
		}
	],
	"settings": {}
	},
	"meta": {
		"version": "2",
		"is-incomplete": false,
		"debug": false
	}
}
```

### Script runner

```php
namespace Foo\Tests\Integration;

use SMW\Tests\LightweightJsonTestCaseScriptRunner;

/**
 * @since ...
 */
class CustomJsonScriptTest extends LightweightJsonTestCaseScriptRunner {

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

	/**
	 * @see JsonTestCaseScriptRunner::runTestCaseFile
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		// Checks environment, runs default assertions
		parent::runTestCaseFile( $jsonTestCaseFileHandler );

		$this->doRunMyTests( $jsonTestCaseFileHandler );
	}

	private function doRunMyTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'myType' );

		if ( $testCases === [] ) {
			return;
		}

		foreach ( $testCases as $case ) {

			// Assert
		}
	}
}
```

## Requirements

Describe methods and classes require SMW 3.1+.

[bootstrap.json]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/bootstrap.json
[design.md]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/docs/design.md
[testcase]: https://phpunit.de/manual/6.5/en/writing-tests-for-phpunit.html