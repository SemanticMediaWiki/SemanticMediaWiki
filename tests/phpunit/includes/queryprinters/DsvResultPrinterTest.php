<?php

namespace SMW\Test;

use SMWDSVResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * Tests for the SMWDSVResultPrinter class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMWDSVResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class DsvResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWDSVResultPrinter';
	}

	/**
	 * Helper method that returns a SMWDSVResultPrinter object
	 *
	 * @return SMWDSVResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new SMWDSVResultPrinter( 'dsv' ), $parameters );
	}

	/**
	 * @test SMWDSVResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
