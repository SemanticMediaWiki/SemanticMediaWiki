<?php

namespace SMW\Tests;

use SMW\Utils\FileFetcher;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PHPUnitCheckRunnerTest extends \PHPUnit_Framework_TestCase {

	private $checkFailures = [
		// #4564
		'setExpectedException' => [],
		// 'assertInternalType' => []
	];

	public function testCheckDeprecatedUsages() {

		$exceptions = [
			'PHPUnitCompat',
			'PHPUnitCheckRunnerTest'
		];

		$exceptions = array_flip( $exceptions );
		$checks = array_keys( $this->checkFailures );

		$fileFetcher = new FileFetcher( SMW_PHPUNIT_DIR );
		$iterator = $fileFetcher->findByExtension( 'php' );

		foreach ( $iterator as $file => $v ) {

			$pathinfo = pathinfo( $file );

			if ( isset( $exceptions[$pathinfo['filename']] ) ) {
				continue;
			}

			$contents = file_get_contents( $file );

			$this->runUsageCheck( $checks, $contents, $pathinfo['basename'] );
		}

		foreach ( $this->checkFailures as $key => $failures ) {
			$this->assertEquals(
				[],
				$failures,
				"\nFailed because listed file(s) contains a deprecated usage of: `$key`\n"
			);
		}

	}

	private function runUsageCheck( $checks, $contents, $basename ) {
		foreach ( $checks as $check ) {

			if ( strpos( $contents, $check ) === false ) {
				continue;
			}

			$this->checkFailures[$check][] = $basename;
			break;
		}
	}

}
