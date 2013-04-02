<?php

namespace SMW\Test;

use SMW\SetParserFunction;
use SMW\ParserData;
use SMW\ParserParameterFormatter;
use SMWDIWikiPage;
use SMWDataItem;
use SMWDataValueFactory;
use Title;
use ParserOutput;

/**
 * Tests for the SMW\SetParserFunction class.
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
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class SetParserFunctionTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// Single data set

			// {{#set:
			// |Foo=bar
			// }}
			array(
				// Title　/ subject
				'Foo',
				// Parameters
				array( 'Foo=bar' ),
				// Expected results
				array(
					'errors' => 0,
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'value' => 'Bar'
				)
			),

			// Empty data set

			// {{#set:
			// |Foo=
			// }}
			array(
				// Title　/ subject
				'Foo',
				// Parameters
				array( 'Foo=' ),
				// Expected results
				array(
					'errors' => 0,
					'propertyCount' => 0,
					'propertyLabel' => '',
					'value' => ''
				)
			),

			// Multiple data set

			// {{#set:
			// |BarFoo=9001
			// |Foo=bar
			// }}
			array(
				// Title　/ subject
				'Foo',
				// Parameters
				array( 'Foo=bar', 'BarFoo=9001' ),
				// Expected results
				array(
					'errors' => 0,
					'propertyCount' => 2,
					'propertyLabel' => array( 'Foo', 'BarFoo' ),
					'value' => array( 'Bar', '9001' )
				)
			),

			// Multiple data set with an error record

			// {{#set:
			// |_Foo=9001 --> will raise an error
			// |Foo=bar
			// }}
			array(
				'Foo', // Title
				array( 'Foo=bar', '_Foo=9001' ), // Parameters
				array(
					'errors' => 1,
					'propertyCount' => 1,
					'propertyLabel' => array( 'Foo' ),
					'value' => array( 'Bar' )
				)
			),

		);
	}

	/**
	 * Helper method to get Title object
	 *
	 * @return Title
	 */
	private function getTitle( $title ){
		return Title::newFromText( $title );
	}

	/**
	 * Helper method to get ParserOutput object
	 *
	 * @return ParserOutput
	 */
	private function getParserOutput(){
		return new ParserOutput();
	}

	/**
	 * Helper method
	 *
	 * @return  SMW\SetParserFunction
	 */
	private function getInstance( $title, $parserOutput ) {
		return new SetParserFunction( new ParserData( $this->getTitle( $title ), $parserOutput ) );
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = $this->getInstance( $title , $this->getParserOutput() );
		$this->assertInstanceOf( 'SMW\SetParserFunction', $instance );
	}

	/**
	 * Test instance exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructorException( $title ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new SetParserFunction( $title );
	}

	/**
	 * Test parse()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testParse( $title, array $params, array $expected ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertInternalType( 'string', $instance->parse( new ParserParameterFormatter( $params ) ) );
	}

	/**
	 * Test instantiated property and value strings
	 *
	 * @dataProvider getDataProvider
	 */
	public function testInstantiatedPropertyValues( $title, array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$instance = $this->getInstance( $title, $parserOutput );

		// Black-box
		$instance->parse( new ParserParameterFormatter( $params ) );

		// Re-read data from stored parserOutput
		$parserData = new ParserData( $this->getTitle( $title ), $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getSemanticData() );
		$this->assertCount( $expected['propertyCount'], $parserData->getSemanticData()->getProperties() );

		// Check added properties
		foreach ( $parserData->getSemanticData()->getProperties() as $key => $diproperty ){
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
			$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );

			// Check added property values
			foreach ( $parserData->getSemanticData()->getPropertyValues( $diproperty ) as $dataItem ){
				$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
				if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['value'] );
				}
			}
		}
	}
}