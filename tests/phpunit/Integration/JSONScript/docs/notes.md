## Technical notes

<pre>
SMW\Tests
│
├─ JsonTestCaseScriptRunner
│	└─ LightweightJsonTestCaseScriptRunner
│		└─ ExtendedJsonTestCaseScriptRunner
├─ \Integration
│	└─ \JSONScript
│		└─ JsonTestCaseScriptRunnerTest
└─ \Utils
	└─ \JSONScript
		└─ ...
</pre>

- The `JSON` is internally transformed into a corresponding `PHPUnit` dataset with the help of the `JsonTestCaseContentHandler` and `JsonTestCaseScriptRunner`.
-  A test file (e.g "myTest.json") will be loaded from the specified location in `JsonTestCaseScriptRunner::getTestCaseLocation` and is automatically run during
the `PHPUnit` test execution.
- The `readmeContentsBuilder.php` can be used to update the list of available test cases including its descriptions.

### Script runners

- `JsonTestCaseScriptRunner` is the base runner that provides all methods necessary to run test cases, it also includes version checks as well as to validate custom defined dependencies
- `LightweightJsonTestCaseScriptRunner` allows to use the `parser`, `parser-html`, `special`, and `semantic-data` type assertions
- `ExtendedJsonTestCaseScriptRunner` provides additional assertion methods