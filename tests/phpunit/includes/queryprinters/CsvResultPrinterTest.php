<?php

namespace SMW\Test;

use SMWCsvResultPrinter;

use ReflectionClass;

/**
 * Tests for the CsvResultPrinter class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class CsvResultPrinterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWCsvResultPrinter';
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
	 * Helper method that returns a SMWCsvResultPrinter object
	 *
	 * @return SMWCsvResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		$format = 'csv';

		$instance = new SMWCsvResultPrinter( $format );

		$reflector = new ReflectionClass( $this->getClass() );
		$params = $reflector->getProperty( 'params' );
		$params->setAccessible(true);
		$params->setValue( $instance, $parameters );

		return $instance;
	}

	/**
	 * @test SMWCsvResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test SMWCsvResultPrinter::getFileName
	 *
	 * @since 1.9
	 */
	public function testGetFileName() {

		$filename = $this->getRandomString() . ' ' . $this->getRandomString();
		$instance = $this->getInstance( array( 'filename' => $filename ) );

		$this->assertEquals( $filename, $instance->getFileName( $this->getMockQueryResult() ) );
	}

}
