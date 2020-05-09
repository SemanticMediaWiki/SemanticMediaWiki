<?php

namespace SMW\Tests\Structure;

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

	private static $iterator;

	public static function setUpBeforeClass() : void {

		$fileFetcher = new FileFetcher(
			SMW_PHPUNIT_DIR
		);

		self::$iterator = $fileFetcher->findByExtension( 'php' );
	}

	/**
	 * @see https://phpunit.de/announcements/phpunit-8.html
	 */
	public function testCheckMissingVoidType() {

		$exceptions = [
			'PHPUnitCheckRunnerTest'
		];

		$exceptions = array_flip( $exceptions );

		$missingVoidTest = [
			'setUp' => [],
			'tearDown' => [],
			'setUpBeforeClass' => [],
			'tearDownAfterClass' => []
		];

		foreach ( self::$iterator as $file => $v ) {

			$pathinfo = pathinfo( $file );

			if ( isset( $exceptions[$pathinfo['filename']] ) ) {
				continue;
			}

			$contents = file_get_contents( $file );

			if ( (
				strpos( $contents, 'function setUp() {' ) ||
				strpos( $contents, 'function setUp(){' ) ) !== false ) {
				$missingVoidTest['setUp'][] = $pathinfo['basename'];
			} elseif ( (
				strpos( $contents, 'function tearDown() {' ) ||
				strpos( $contents, 'function tearDown(){' ) ) !== false ) {
				$missingVoidTest['tearDown'][] = $pathinfo['basename'];
			} elseif ( (
				strpos( $contents, 'function setUpBeforeClass() {' ) ||
				strpos( $contents, 'function setUpBeforeClass(){' ) ) !== false ) {
				$missingVoidTest['setUpBeforeClass'][] = $pathinfo['basename'];
			} elseif ( (
				strpos( $contents, 'function tearDownAfterClass() {' ) ||
				strpos( $contents, 'function tearDownAfterClass(){' ) ) !== false ) {
				$missingVoidTest['tearDownAfterClass'][] = $pathinfo['basename'];
			}
		}

		foreach ( $missingVoidTest as $key => $files ) {
			$this->assertEquals(
				[],
				$files,
				"\nFailed because listed file(s) is missing a `: void` type for `$key`!\n"
			);
		}
	}

	public function testCheckDeprecatedUsages() {

		$exceptions = [
			'PHPUnitCompat',
			'PHPUnitCheckRunnerTest'
		];

		$exceptions = array_flip( $exceptions );
		$message = '';

		$deprecatedUsageCheckFailures = [
			// #4564
			'setExpectedException' => [],
			'assertInternalType' => [],
			'assertContains' => [],
			'assertNotContains' => []
		];

		foreach ( self::$iterator as $file => $v ) {

			$pathinfo = pathinfo( $file );

			if ( isset( $exceptions[$pathinfo['filename']] ) ) {
				continue;
			}

			$contents = file_get_contents( $file );

			if ( strpos( $contents, 'setExpectedException' ) !== false ) {
				$deprecatedUsageCheckFailures['setExpectedException'][] = $pathinfo['basename'];
				$message = "Failed because listed file(s) contain(s) a deprecated usage of `setExpectedException`";
			} elseif( strpos( $contents, 'assertInternalType' ) !== false && strpos( $contents, 'use PHPUnitCompat' ) === false ) {
				$deprecatedUsageCheckFailures['assertInternalType'][] = $pathinfo['basename'];
				$message = "Failed because listed file(s) contain(s) a `assertInternalType`\nusage without the `use PHPUnitCompat` trait!";
			} elseif( strpos( $contents, 'assertContains' ) !== false && strpos( $contents, 'use PHPUnitCompat' ) === false ) {
				$deprecatedUsageCheckFailures['assertContains'][] = $pathinfo['basename'];
				$message = "Failed because listed file(s) contain(s) a `assertContains`\nusage without the `use PHPUnitCompat` trait!";
			} elseif( strpos( $contents, 'assertNotContains' ) !== false && strpos( $contents, 'use PHPUnitCompat' ) === false ) {
				$deprecatedUsageCheckFailures['assertNotContains'][] = $pathinfo['basename'];
				$message = "Failed because listed file(s) contain(s) a `assertNotContains`\nusage without the `use PHPUnitCompat` trait!";
			}
		}

		foreach ( $deprecatedUsageCheckFailures as $key => $files ) {
			$this->assertEquals( [], $files, "$message\n" );
		}
	}

}
