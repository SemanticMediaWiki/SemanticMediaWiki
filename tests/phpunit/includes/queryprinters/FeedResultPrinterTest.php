<?php

namespace SMW\Test;

use SMW\FeedResultPrinter;

/**
 * Tests for the FeedResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\FeedResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class FeedResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FeedResultPrinter';
	}

	/**
	 * Helper method that returns a FeedResultPrinter object
	 *
	 * @return FeedResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new FeedResultPrinter( 'feed' ), $parameters );
	}

	/**
	 * @test FeedResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
