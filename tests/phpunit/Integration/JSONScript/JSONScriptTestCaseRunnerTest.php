<?php

namespace SMW\Tests\Integration\JSONScript;

use SMW\Tests\JSONScriptServicesTestCaseRunner;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class JSONScriptTestCaseRunnerTest extends JSONScriptServicesTestCaseRunner {

	/**
	 * @see JSONScriptServicesTestCaseRunner::runTestAssertionForType
	 */
	protected function runTestAssertionForType( string $type ) : bool {

		$expectedAssertionTypes = [
			'parser',
			'parser-html',
			'special',
			'rdf',
			'query',
			'api'
		];

		return in_array( $type, $expectedAssertionTypes );
	}

	/**
	 * @see JSONScriptTestCaseRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

	/**
	 * @see JSONScriptTestCaseRunner::getTestCaseLocation
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '2';
	}

	/**
	 * @see JSONScriptTestCaseRunner::getDependencyDefinitions
	 */
	protected function getDependencyDefinitions() {
		return [
			'Maps' => function( $val, &$reason ) {

				if ( !defined( 'SM_VERSION' ) ) {
					$reason = "Dependency: Maps (or Semantic Maps) as requirement for the test is not available!";
					return false;
				}

				list( $compare, $requiredVersion ) = explode( ' ', $val );
				$version = SM_VERSION;

				if ( !version_compare( $version, $requiredVersion, $compare ) ) {
					$reason = "Dependency: Required version of Maps ($requiredVersion $compare $version) is not available!";
					return false;
				}

				return true;
			},
			'ext-intl' => function( $val, &$reason ) {

				if ( !extension_loaded( 'intl' ) ) {
					$reason = "Dependency: ext-intl (PHP extension, ICU collation) as requirement for the test is not available!";
					return false;
				}

				return true;
			},
			'ICU' => function( $val, &$reason ) {

				if ( !extension_loaded( 'intl' ) ) {
					$reason = "Dependency: ext-intl (PHP extension, ICU collation) as requirement for the test is not available!";
					return false;
				}

				list( $compare, $requiredVersion ) = explode( ' ', $val );
				$version = INTL_ICU_VERSION;

				if ( !version_compare( $version, $requiredVersion, $compare ) ) {
					$reason = "Dependency: Requires at least ICU version {$requiredVersion} but only {$version} is available!";
					return false;
				}

				return true;
			}
		];
	}

}

class_alias( JSONScriptTestCaseRunnerTest::class, 'SMW\Tests\Integration\JSONScript\JsonTestCaseScriptRunnerTest' );
