## Test design and usage

The `JSONScript` follows the arrange, act, assert approach, with the `setup` section containing object definitions that are planned to be used during a test. The section expects that an entity page and its contents (generally the page content in wikitext, annotations etc.) to follow a predefined structure.

It is also possible to [import](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/JSONScript/TestCases/p-0211.json) larger text passages or [upload files](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/JSONScript/TestCases/p-0705.json) for a test scenario.

When creating test scenarios, it is suggested to use distinct names and subjects to ensure that tests will not interfere with each other and their results. It may also be of advantage to split the setup of data (e.g. `Example/Test/1`) from the actual test subject (e.g. `Example/Test/Q.1`) to avoid conflicting or flaky validations during the assertion process.

<pre>
"setup": [
	{
		"page": "Has text",
		"namespace":"SMW_NS_PROPERTY",
		"contents": "[[Has type::Text]]"
	},
	{
		"page": "Property:Has number",
		"contents": "[[Has type::Number]]"
	},
	{
		"page": "Example/Test/1",
		"namespace":"NS_MAIN",
		"contents": "[[Has text::Some text to search]]"
	},
	{
		"page": "Example/Test/Q.1",
		"namespace":"NS_MAIN",
		"contents": "{{#ask: [[Has text::~Some text*]] |?Has text }}"
	}
],
</pre>

The [bootstrap.json][bootstrap.json] contains an example and can be used as starting point for creating a new test case.

### Test assertions

* The `type` provides specialized assertion methods with some of them requiring an extra setup to yield a comparable output but in most cases the `parser` type
should suffice to create test assertions for common test scenarios. Available types are:
  * `query`, `concept`, and `format`
  * `parser`
  * `parser-html`
  * `rdf`
  * `special`
* The `about` describes what the test is expected to test which may help during a failure to identify potential conflicts or hints on how to resolve an issue.
* The `subject` refers to the page that was defined in the `setup` section.

For example, as of version 2 the `parser` type (`ParserTestCaseProcessor`) knows two assertions methods:

- `assert-store` is to validate data against `Store::getSemanticData`
- `assert-output` is to validate string comparison against the `ParserOutput` generated text

#### Type `parser`

The test result assertion provides simplified string comparison methods (mostly for output related assertion but expressive enough for users to understand the test objective and its expected results). For example, verifying that the parser does output a certain string, one has to the define an expected output.

<pre>
"tests": [
	{
		"type": "parser",
		"about": "#0 test output of the [[ ... ]] annotation",
		"subject": "Example/Test/1",
		"assert-output": {
			"to-contain": [
				"Some text to search"
			],
			"not-contain": [
				"abc"
			]
		}
	},
	{
		"type": "parser",
		"about": "#1 test output of #ask query",
		"subject": "Example/Test/Q.1",
		"assert-output": {
			"include-head-items": true,
			"to-contain": [
				"Some text to search"
			],
			"not-contain": [
				"abc"
			]
		}
	}
]
</pre>

- `include-head-items` is an option that fetches the information stored in `ParserOutput::getHeadItems` and appends it to the validation output

#### Type `parser-html`

To verify that the HTML code produced by the parser conforms to a certain structure the test type `parser-html` may be used. With this type the expected
output structure may be specified as a CSS selector. The test will succeed if at least one element according to that selector is found in the output.

Example:
<pre>
"tests": [
	{
		"type": "parser-html",
		"about": "#0 Basic List format",
		"subject": "Example/0401",
		"assert-output": {
			"to-contain": [
				"p > a[ title='Bar' ] + a[ title='Baz' ] + a[ title='Foo' ] + a[ title='Quok' ]"
			]
		}
	}
]
</pre>

For further details and limitations on the CSS selectors see the description of the [Symfony `CssSelector` Component][css_selector] that is
used for this test type.

It is also possible to require an exact number of occurrences of HTML elements by providing an array instead of just a CSS selector string.

Example:
<pre>
		"assert-output": {
			"to-contain": [
				[ "p > a", 4 ]
			]
		}
</pre>

Finally the general well-formedness of the HTML can be tested, although this will not fail for recoverable errors (see the documentation on PHP's
[DOMDocument::loadHTML][domdocument]).

Example:
<pre>
		"assert-output": {
			"to-be-valid-html": true,
		}
</pre>

### Preparing the test environment

It can happen that an output is mixed with language dependent content (site vs. page content vs. user language) and therefore it is recommended to fix those
settings for a test by adding something like:

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

By default not all settings parameter are enabled in `JsonTestCaseScriptRunner::prepareTest` and may require an extension in case a specific test case depends on additional customization.

Each `json` file expects a `meta` section with:

- `version` to correspond to the
   `JsonTestCaseScriptRunner::getRequiredJsonTestCaseMinVersion` and controls the
  JSON script definition that the runner is expected to support.
- `is-incomplete` removes the file from the test plan if set `true`
- `debug` as flag for support of intermediary debugging that may output internal
  object state information.

<pre>
"meta": {
	"version": "2",
	"is-incomplete": false,
	"debug": false
}
</pre>

### Define a dependency

Some test scenarios may require an extension or another component and to check those dependencies before the actual test is run, use `requires` as in:

<pre>
"requires": {
	"Maps": ">= 5.0"
},
</pre>

### Skipping a test or mark as incomplete

Sometimes certain data can cause inconsistencies with an environment hence it is possible to skip those cases by adding:

<pre>
{
	"skip-on": {
		"virtuoso": "Virtuoso 6.1 does not support BC/BCE dates"
	},
	"page": "Example/P0413/11",
	"contents": "[[Has date::Jan 1 300 BC]]"
},
</pre>

<pre>
{
	"skip-on": {
		"hhvm-*": "HHVM (or SQLite) shows opposite B1000, B9",
		"mediawiki": [ ">1.30.x", "MediaWiki changed ..." ],
		"smw": [ ">2.5.x", "SMW changed ..." ]
	}
}
</pre>

Constraints that include `hhvm-*` will indicate to exclude all HHVM versions while `>1.30.x` defines that any MW version greater than 1.30 should be ignored.

It is also possible that an entire test scenario cannot be completed in a particular environment therefore it can be marked and skipped with:

<pre>
"meta": {
	"skip-on": {
		"virtuoso": "Some info as to why it is skipped.",
		"sqlite": "...",
		"postgres": "..."
	},
	"version": "2",
	"is-incomplete": false,
	"debug": false
}
</pre>

If a test is incomplete for some reason, use the `is-incomplete` field to indicate the status which henceforth avoids a test execution.

### Test case file naming

The naming of a test file is arbitrary but it has been a best practice to indicate the type of test expected to be executed. For example, `s-0001.json` would indicate that the test is mostly concerned with special pages while `p-0001.json` is to handle parser output related assertions.

### Debugging and running a test

Tests are run together using the `composer phpunit` or `composer test` command but it may not always be feasible especially when trying to debug or design a new test case.

There are two methods that can help restrict the execution during the design or debug phase:

* Modify the `JsonTestCaseScriptRunner::getAllowedTestCaseFiles`  to take an argument such as a file name ( e.g. `s-0014.json`) to restrict the execution of a test which is mostly done when running from an IDE editor
* The command line allows to invoke a filter argument to specify a case such as `composer test -- --filter s-0014.json`

<pre>
$  composer test -- --filter s-0014.json
Using PHP 5.6.8

Semantic MediaWiki: 2.5.0-alpha (SMWSQLStore3, mysql)
MediaWiki:          1.28.0-alpha (MediaWiki vendor autoloader)
Site language:      en

Execution time:     2017-01-01 12:00
Debug logs:         Enabled
Xdebug:             Disabled (or not installed)

phpunit 4.8.24 by Sebastian Bergmann and contributors.

Runtime:        PHP 5.6.8
Configuration:  ...\extensions\SemanticMediaWiki\phpunit.xml.dist

.

Time: 13.02 seconds, Memory: 34.00Mb

OK (1 test, 16 assertions)
</pre>

The following [video](https://youtu.be/7fDKjPFaTaY) contains a very brief introduction on how to run and debug a JSONScript test case. An introduction to the test environment can be found [here][README.md].

[README.md]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/README.md
[bootstrap.json]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/bootstrap.json
[css_selector]: https://symfony.com/doc/current/components/css_selector.html
[domdocument]: http://php.net/manual/en/domdocument.loadhtml.php#refsect1-domdocument.loadhtml-errors
