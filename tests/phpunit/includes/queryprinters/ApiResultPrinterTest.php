<?php

namespace SMW\Test;

use SMW\ApiResultPrinter;

/**
 * Tests for the ApiResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ApiResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class ApiResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiResultPrinter';
	}

	/**
	 * Helper method that returns a ApiResultPrinter object
	 *
	 * @return ApiResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( $this->getMockForAbstractClass( $this->getClass(), array( 'api' ) ), $parameters );
	}

	/**
	 * @test ApiResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
