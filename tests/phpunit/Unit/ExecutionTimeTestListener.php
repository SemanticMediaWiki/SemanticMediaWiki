<?php

namespace SMW\Tests;

use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestListener;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Framework_Warning;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class ExecutionTimeTestListener implements PHPUnit_Framework_TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Internal tracking for test suites
	 */
	protected $suites = 0;

	/**
	 * Threshold that defines "slow" in terms of seconds
	 *
	 * @var integer
	 */
	protected $slowThreshold = 10;

	protected $slowTests = [];
	protected $isEnabledToListen = true;

	public function __construct( $isEnabledToListen, $slowThreshold ) {
		$this->isEnabledToListen = $isEnabledToListen;
		$this->slowThreshold = $slowThreshold;
	}

	/**
	 * @see PHPUnit_Framework_TestListener::endTest
	 */
	public function endTest( PHPUnit_Framework_Test $test, $length ) : void {

		if ( $this->isEnabledToListen && ( $length > $this->slowThreshold ) ) {
			$className = get_class( $test );
			$className = substr( $className, strrpos( $className, '\\') + 1 );

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
	 * @see PHPUnit_Framework_TestListener::startTestSuite
	 */
	public function startTestSuite( PHPUnit_Framework_TestSuite $suite ) : void {
		$this->suites++;
	}

	/**
	 * @see PHPUnit_Framework_TestListener::endTestSuite
	 */
	public function endTestSuite( PHPUnit_Framework_TestSuite $suite ) : void {
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
		$suite->_slowTestsReport = [
			'slowThreshold' => $this->slowThreshold,
			'slowTests' => $this->slowTests
		];

		unset( $this->slowTests );
	}

}
