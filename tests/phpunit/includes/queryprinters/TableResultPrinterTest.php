<?php

namespace SMW\Test;

use SMW\TableResultPrinter;
use SMW\ResultPrinter;
use SMWPrintRequest;

use ReflectionClass;

/**
 * Tests for the TableResultPrinter class
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
 * @covers \SMW\TableResultPrinter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class TableResultPrinterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\TableResultPrinter';
	}

	/**
	 * Helper method that returns a SMWPrintRequest object
	 *
	 * @since 1.9
	 *
	 * @return SMWPrintRequest
	 */
	private function getMockPrintRequest( $text = 'Foo' ) {

		$printRequest = $this->getMockBuilder( 'SMWPrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$printRequest->expects( $this->any() )
			->method( 'getMode' )
			->will( $this->returnValue( SMWPrintRequest::PRINT_THIS ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( 'center' ) );

		return $printRequest;
	}


	/**
	 * Helper method that returns a SMWDataValue object
	 *
	 * @since 1.9
	 *
	 * @return SMWDataValue
	 */
	private function getMockDataValue( $text = null ) {

		$dataItem = $this->getSubject();
		$typeId   = '_wpg';

		$dataValue = $this->getMockBuilder( 'SMWWikiPageValue' )
			->disableOriginalConstructor()
			->getMock();

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( $typeId ) );

		$dataValue->expects( $this->any() )
			->method( 'getShortText' )
			->will( $this->returnValue( $text === null ? $dataItem->getTitle()->getText() : $text ) );

		return $dataValue;
	}

	/**
	 * Helper method that returns a SMWResultArray object
	 *
	 * @since 1.9
	 *
	 * @return SMWResultArray
	 */
	private function getMockResultArray( $text = 'Bar' ) {

		$resultArray = $this->getMockBuilder( 'SMWResultArray' )
			->disableOriginalConstructor()
			->getMock();

		$resultArray->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$resultArray->expects( $this->exactly( 2 ) )
			->method( 'getPrintRequest' )
			->will( $this->returnValue( $this->getMockPrintRequest() ) );

		$resultArray->expects( $this->any() )
			->method( 'getNextDataValue' )
			->will( $this->onConsecutiveCalls( $this->getMockDataValue( $text ), false ) );

		return $resultArray;
	}

	/**
	 * Helper method that returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	private function getMockQueryResult( array $printRequests, array $resultArray ) {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->will( $this->returnValue( count( $resultArray ) ) );

		$queryResult->expects( $this->any() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( $printRequests ) );

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( true ) );

		$queryResult->expects( $this->any() )
			->method( 'getLink' )
			->will( $this->returnValue( new \SMWInfolink( true, 'Lala' , 'Lula' ) ) );

		// Word of caution, onConsecutiveCalls is used in order to ensure
		// that a while() loop is not trapped in an infinite loop and returns
		// a false at the end
		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->onConsecutiveCalls( $resultArray , false ) );

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
	 * Helper method that returns a TableResultPrinter object
	 *
	 * @return TableResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new TableResultPrinter( 'table' ), $parameters );
	}

	/**
	 * @test TableResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test TableResultPrinter::getResultText
	 *
	 * @since 1.9
	 */
	public function testGetResultText() {

		$random = array(
			'pr-1' => 'PR-' . $this->getRandomString(),
			'pr-2' => 'PR-' . $this->getRandomString(),
			'ra-1' => 'RA-' . $this->getRandomString(),
			'ra-2' => 'RA-' . $this->getRandomString(),
		);

		$parameters = array(
			'headers'   => SMW_HEADERS_PLAIN,
			'class'     => 'tableClass',
			'format'    => 'table',
			'offset'    => 0,
			'transpose' => false
		);

		// Table matcher, expecting randomly generate strings
		// to be present in a certain order and context
		$matcher = array(
			'tag' => 'table', 'attributes' => array( 'class' => $parameters['class'] ),
			'descendant' => array(
				'tag' => 'th', 'content' => $random['pr-1'], 'attributes' => array( 'class' => $random['pr-1'] ),
				'tag' => 'th', 'content' => $random['pr-2'], 'attributes' => array( 'class' => $random['pr-2'] ),
			),
			'descendant' => array(
				'tag' => 'tr',
				'child' => array(
					'tag' => 'td', 'content' => $random['ra-1'], 'attributes' => array( 'class' => $random['pr-1'] ),
					'tag' => 'td', 'content' => $random['ra-2'], 'attributes' => array( 'class' => $random['pr-2'] )
				)
			),
			'descendant' => array(
				'tag' => 'tr', 'attributes' => array( 'class' => 'smwfooter' ),
				'child' => array(
					'tag' => 'td', 'attributes' => array( 'class' => 'sortbottom' ),
				)
			)
		);

		// Set-up and inject necessary objects
		$instance = $this->getInstance( $parameters );

		$printRequests = array(
			$this->getMockPrintRequest( $random['pr-1'] ),
			$this->getMockPrintRequest( $random['pr-2'] )
		);
		$resultArray   = array(
			$this->getMockResultArray( $random['ra-1'] ),
			$this->getMockResultArray( $random['ra-2'] )
		);

		// Access protected methods and properties
		$reflector = new ReflectionClass( $this->getClass() );

		$property = $reflector->getProperty( 'fullParams' );
		$property->setAccessible( true );
		$property->setValue( $instance, array() );

		$method = $reflector->getMethod( 'linkFurtherResults' );
		$method->setAccessible( true );
		$method->invoke( $instance, $this->getMockQueryResult( $printRequests, $resultArray ) );

		$method = $reflector->getMethod( 'getResultText' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, $this->getMockQueryResult( $printRequests, $resultArray ), SMW_OUTPUT_FILE );
		$this->assertInternalType( 'string', $result );
		$this->assertTag( $matcher, $result );

	}
}
