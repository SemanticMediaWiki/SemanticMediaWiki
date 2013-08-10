<?php

namespace SMW\Test;

use SMWEmbeddedResultPrinter;

/**
 * Tests for the SMWEmbeddedResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMWEmbeddedResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class EmbeddedResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWEmbeddedResultPrinter';
	}

	/**
	 * Helper method that returns a SMWEmbeddedResultPrinter object
	 *
	 * @return SMWEmbeddedResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new SMWEmbeddedResultPrinter( 'embedded' ), $parameters );
	}

	/**
	 * @test SMWEmbeddedResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
