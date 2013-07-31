<?php

namespace SMW\Test;

use SMW\FeedResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * Tests for the FeedResultPrinter class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
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
