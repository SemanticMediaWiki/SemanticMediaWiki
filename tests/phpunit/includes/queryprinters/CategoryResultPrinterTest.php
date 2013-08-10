<?php

namespace SMW\Test;

use SMWCategoryResultPrinter;

/**
 * Tests for the SMWCategoryResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMWCategoryResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class CategoryResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWCategoryResultPrinter';
	}

	/**
	 * Helper method that returns a SMWCategoryResultPrinter object
	 *
	 * @return SMWCategoryResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new SMWCategoryResultPrinter( 'category' ), $parameters );
	}

	/**
	 * @test SMWCategoryResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
