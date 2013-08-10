<?php

namespace SMW\Test;

use SMWRDFResultPrinter;

/**
 * Tests for the SMWRDFResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMWRDFResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class RdfResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWRDFResultPrinter';
	}

	/**
	 * Helper method that returns a SMWRDFResultPrinter object
	 *
	 * @return SMWRDFResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new SMWRDFResultPrinter( 'rdf' ), $parameters );
	}

	/**
	 * @test SMWRDFResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
