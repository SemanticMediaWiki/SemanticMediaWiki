<?php

namespace SMW\Test;

use SMW\JsonResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * Tests for the JsonResultPrinter class
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
 * @covers \SMW\JsonResultPrinter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class JsonResultPrinterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\JsonResultPrinter';
	}

	/**
	 * Helper method that returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	private function getMockQueryResult( $result = array() ) {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->will( $this->returnValue( count( $result ) ) );

		$queryResult->expects( $this->any() )
			->method( 'serializeToArray' )
			->will( $this->returnValue( $result ) );

		return $queryResult;
	}

	/**
	 * Helper method sets result printer parameters
	 *
	 * @param ResultPrinter $instance
	 * @param array $parameters
	 *
	 * @return ResultPrinter
	 */
	private function setParameters( ResultPrinter $instance, array $parameters ) {

		$reflector = new ReflectionClass( $this->getClass() );
		$params = $reflector->getProperty( 'params' );
		$params->setAccessible( true );
		$params->setValue( $instance, $parameters );

		if ( isset( $parameters['searchlabel'] ) ) {
			$searchlabel = $reflector->getProperty( 'mSearchlabel' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['searchlabel'] );
		}

		if ( isset( $parameters['headers'] ) ) {
			$searchlabel = $reflector->getProperty( 'mShowHeaders' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['headers'] );
		}

		return $instance;

	}

	/**
	 * Helper method that returns a JsonResultPrinter object
	 *
	 * @return JsonResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new JsonResultPrinter( 'json' ), $parameters );
	}

	/**
	 * @test JsonResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test JsonResultPrinter::getFileName
	 *
	 * @since 1.9
	 */
	public function testGetFileName() {

		$filename = $this->getRandomString() . ' ' . $this->getRandomString();
		$expected = str_replace( ' ', '_', $filename ) . '.json';
		$instance = $this->getInstance( array( 'searchlabel' => $filename ) );

		$this->assertEquals( $expected, $instance->getFileName( $this->getMockQueryResult() ) );
	}

	/**
	 * @test JsonResultPrinter::getResultText
	 *
	 * @since 1.9
	 */
	public function testGetResultText() {

		$result = array(
			'lala' => $this->getRandomString(),
			'lula' => $this->getRandomString()
		);

		$expected = array_merge( $result, array( 'rows' => count( $result ) ) );

		$instance = $this->getInstance( array( 'prettyprint' => false ) );

		$reflector = new ReflectionClass( $this->getClass() );
		$getResultText = $reflector->getMethod( 'getResultText' );
		$getResultText->setAccessible( true );

		$results = $getResultText->invoke( $instance, $this->getMockQueryResult( $result ), SMW_OUTPUT_FILE );

		$this->assertInternalType( 'string', $results );
		$this->assertEquals( json_encode( $expected ), $results );

	}

}
