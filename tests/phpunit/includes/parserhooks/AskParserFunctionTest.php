<?php

namespace SMW\Test;

use SMW\AskParserFunction;
use SMW\ParserData;
use SMW\QueryData;

use SMWDIProperty;
use SMWDIBlob;
use SMWDINumber;
use SMWDataItem;
use SMWDataValueFactory;
use Title;
use MWException;
use ParserOutput;

/**
 * Tests for the SMW\AskParserFunction class.
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
class AskParserFunctionTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// {{#ask: [[Modification date::+]]
			// |?Modification date
			// |format=list
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]]',
					'?Modification date',
					'format=list'
				),
				array(
					'result' => false,
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
				)
			),

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=list'
				),
				array(
					'result' => false,
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

			// Unknown format, default table

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=bar
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=bar'
				),
				array(
					'result' => false,
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

		);
	}

	/**
	 * Helper method to get title object
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
	 * @return SMW\AskParserFunction
	 */
	private function getInstance( $title, $parserOutput = '' ) {
		return new AskParserFunction(
			new ParserData( $this->getTitle( $title ), $parserOutput ),
			new QueryData( $this->getTitle( $title ) )
		);
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertInstanceOf( 'SMW\AskParserFunction', $instance );
	}

	/**
	 * Test instance exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructorException( $title ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = $this->getInstance( $title );
	}

	/**
	 * Test parse()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testParse( $title, array $params, array $expected ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		// $this->assertEquals( $expected['result'], $instance->parse( $params ) !== '' );
		$this->assertTrue( is_string( $instance->parse( $params ) ) );
	}

	/**
	 * Test ($GLOBALS['smwgQEnabled'] = false)
	 *
	 * @dataProvider getDataProvider
	 */
	public function testParseDisabledsmwgQEnabled( $title, array $params ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$expected = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		$this->assertEquals( $expected , $instance->parse( $params, false ) );
	}

	/**
	 * Test generated query data
	 *
	 * @dataProvider getDataProvider
	 */
	public function testInstantiatedQueryData( $title, array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$instance = $this->getInstance( $title, $parserOutput );

		// Black-box approach
		$instance->parse( $params, true );

		// Get semantic data from the ParserOutput that where stored earlier
		// during parse()
		$parserData = new ParserData( $this->getTitle( $title ), $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getSemanticData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getSemanticData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertCount( $expected['queryCount'], $containerSemanticData->getProperties() );

			// Confirm added properties
			foreach ( $containerSemanticData->getProperties() as $key => $diproperty ){
				$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
				$this->assertContains( $diproperty->getKey(), $expected['queryKey'] );

				// Confirm added property values
				foreach ( $containerSemanticData->getPropertyValues( $diproperty ) as $dataItem ){
					$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
					if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
						$this->assertContains( $dataValue->getWikiValue(), $expected['queryValue'] );
					} else if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_NUMBER ){
						$this->assertContains( $dataValue->getNumber(), $expected['queryValue'] );
					} else if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_BLOB ){
						$this->assertContains( $dataValue->getWikiValue(), $expected['queryValue'] );
					}
				}
			}
		}
	}
}
