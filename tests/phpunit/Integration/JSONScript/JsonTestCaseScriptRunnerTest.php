<?php

namespace SMW\Tests\Integration\JSONScript;

use SMW\Tests\ExtendedJsonTestCaseScriptRunner;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class JsonTestCaseScriptRunnerTest extends ExtendedJsonTestCaseScriptRunner {

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '2';
	}

	/**
	 * @see JsonTestCaseScriptRunner::getDependencyDefinitions
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
