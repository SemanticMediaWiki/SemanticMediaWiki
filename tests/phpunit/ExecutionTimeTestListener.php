<?php

namespace SMW\Tests;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use WeakMap;

class ExecutionTimeTestListener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Internal tracking for test suites
	 */
	protected $suites = 0;

	/**
	 * Threshold that defines "slow" in terms of seconds
	 *
	 * @var int
	 */
	protected $slowThreshold = 10;

	protected $slowTests = [];
	protected $isEnabledToListen = true;

	/**
	 * Map of test suites to execution time reports.
	 * @var WeakMap|null
	 */
	private static $slowTestsReport;

	public function __construct( $isEnabledToListen, $slowThreshold ) {
		$this->isEnabledToListen = $isEnabledToListen;
		$this->slowThreshold = $slowThreshold;
		self::$slowTestsReport = class_exists( WeakMap::class ) ? new WeakMap() : null;
	}

	/**
	 * @see TestListener::endTest
	 */
	public function endTest( Test $test, $length ): void {
		if ( $this->isEnabledToListen && ( $length > $this->slowThreshold ) ) {
			$className = get_class( $test );
			$className = substr( $className, strrpos( $className, '\\' ) + 1 );

			$label = sprintf( '%s:%s', $className, $test->getName() );
			$this->slowTests[$label] = round( $length, 3 );
		}

		// Remove any excessive logging added via the `MediaWikiLoggerPHPUnitTestListener`
		// https://github.com/wikimedia/mediawiki/commit/96657099fc69242ecb5e3c09a79e86ea6bbe2c0b
		if ( isset( $test->_formattedMediaWikiLogs ) ) {
			unset( $test->_formattedMediaWikiLogs );
		}
	}

	/**
	 * @see TestListener::startTestSuite
	 */
	public function startTestSuite( TestSuite $suite ): void {
		$this->suites++;
	}

	/**
	 * @see TestListener::endTestSuite
	 */
	public function endTestSuite( TestSuite $suite ): void {
		$this->suites--;

		// Is the last test? Attach the report!
		if ( $this->suites == 0 && $this->slowTests !== [] ) {
			$this->reportSlowTests( $suite );
		}
	}

	private function reportSlowTests( $suite ) {
		arsort( $this->slowTests );

		// Have the PHPUnitResultPrinter to make the actual output in order to
		// have acces to the output buffer used by PHPUnit
		if ( self::$slowTestsReport ) {
			// Use a WeakMap to store the report on PHP 8.0+ to avoid dynamic property creation warnings.
			self::$slowTestsReport[$suite] = [
				'slowThreshold' => $this->slowThreshold,
				'slowTests' => $this->slowTests
			];
		} else {
			$suite->_slowTestsReport = [
				'slowThreshold' => $this->slowThreshold,
				'slowTests' => $this->slowTests
			];
		}

		unset( $this->slowTests );
	}

	/**
	 * Get the execution time report for the given suite.
	 * @param TestSuite $suite The test suite to get the report for.
	 * @return array|null The execution time report, or null if not available.
	 */
	public static function getSlowTestsReport( TestSuite $suite ): ?array {
		if ( self::$slowTestsReport ) {
			return self::$slowTestsReport[$suite] ?? null;
		} else {
			return $suite->_slowTestsReport ?? null;
		}
	}
}
