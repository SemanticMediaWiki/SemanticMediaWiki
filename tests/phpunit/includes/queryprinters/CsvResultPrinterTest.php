<?php

namespace SMW\Test;

use SMW\CsvResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * Tests for the CsvResultPrinter class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\CsvResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class CsvResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CsvResultPrinter';
	}

	/**
	 * Helper method that returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	private function getMockQueryResult() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		return $queryResult;
	}

	/**
	 * Helper method that returns a CsvResultPrinter object
	 *
	 * @return CsvResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new CsvResultPrinter( 'csv' ), $parameters );
	}

	/**
	 * @test CsvResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test CsvResultPrinter::getFileName
	 *
	 * @since 1.9
	 */
	public function testGetFileName() {

		$filename = $this->getRandomString() . ' ' . $this->getRandomString();
		$instance = $this->getInstance( array( 'filename' => $filename ) );

		$this->assertEquals( $filename, $instance->getFileName( $this->getMockQueryResult() ) );
	}

}
