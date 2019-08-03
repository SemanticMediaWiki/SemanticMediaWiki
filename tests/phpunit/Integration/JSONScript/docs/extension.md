## Extension usage

Extensions create to their own `JSONScript` integration tests and run them against MediaWiki and Semantic MediaWiki by taking advantage of the existing SMW test infrastructure for integration tests (JSON script interpreter, assertions validators etc.) can:

Add the following lines to the PHPUnit `bootstrap.php`

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

### Using a script runner

Semantic MediaWiki provides two script runners that can be used by extensions:

- `LightweightJsonTestCaseScriptRunner` allows to use the `parser`, `parser-html`, `special`, and `semantic-data` type assertions
- `ExtendedJsonTestCaseScriptRunner` provides additional assertion methods
- `JsonTestCaseScriptRunner` is the base runner that provides all methods necessary to run test cases, it also includes version checks as well as to validate custom defined dependencies

The `LightweightJsonTestCaseScriptRunner` was introduced to help users to quickly create a `CustomTestScriptRunnerTest` without much modification to the `CustomTestScriptRunnerTest` itself besides adding the location of the test case folder.

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

### Creating test cases

The [bootstrap.json][bootstrap.json] contains an example that can be used as starting point for a test case and [design][design.md] document describes in detail options and assertions methods.

### Extending JSON and the script runner

In some cases the selected `JSON` style may vary or contains information that require additional validation therefore the script runner can easily be extended with something like:

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