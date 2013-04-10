<?php

namespace SMW\Test;

use SMW\SubobjectParserFunction;
use SMW\ParserData;
use SMW\Subobject;
use SMW\ParserParameterFormatter;

use SMWDIProperty;
use SMWDataItem;
use SMWDataValueFactory;
use Title;
use MWException;
use ParserOutput;

/**
 * Tests for the SMW\SubobjectParserFunction class.
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
class SubobjectParserFunctionTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		// Get the right language for an error object
		$diPropertyError = new SMWDIProperty( SMWDIProperty::TYPE_ERROR );

		return array(

			// Anonymous identifier
			// {{#subobject:
			// |Foo=bar
			// }}
			array(
				'Foo',
				array( '', 'Foo=bar' ),
				array(
					'errors' => false,
					'name' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'value' => 'Bar'
				)
			),

			// Anonymous identifier
			// {{#subobject:-
			// |Foo=1001 9009
			// }}
			array(
				'Foo',
				array( '-', 'Foo=1001 9009' ),
				array(
					'errors' => false,
					'name' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'value' => '1001 9009'
				)
			),

			// Named identifier
			// {{#subobject:FooBar
			// |FooBar=Bar foo
			// }}
			array(
				'Foo',
				array( 'FooBar', 'FooBar=Bar foo' ),
				array(
					'errors' => false,
					'name' => 'FooBar',
					'propertyCount' => 1,
					'propertyLabel' => 'FooBar',
					'value' => 'Bar foo'
				)
			),

			// Named identifier
			// {{#subobject:Foo bar
			// |Foo=Help:Bar
			// }}
			array(
				'Foo',
				array( 'Foo bar', 'Foo=Help:Bar' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'value' => 'Help:Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// }}
			array(
				'Foo',
				array( ' Foo bar foo ', 'Bar=foo Bar' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 1,
					'propertyLabel' => 'Bar',
					'value' => 'Foo Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |状況=超やばい
			// |Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject
			// }}
			array(
				'Foo',
				array(
					' Foo bar foo ',
					'状況=超やばい',
					'Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( '状況', 'Bar' ),
					'value' => array( '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' )
				)
			),

			// Returns an error due to wrong declaration (see Modification date)

			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// |Modification date=foo Bar
			// }}
			array(
				'Foo',
				array( ' Foo bar foo ', 'Modification date=foo Bar' ),
				array(
					'errors' => true,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Modification date', $diPropertyError->getLabel() ),
					'value' => array( 'Foo Bar', 'Modification date' )
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
	 * @return SMW\SubobjectParserFunction
	 */
	private function getInstance( $title, $parserOutput ) {
		return new SubobjectParserFunction(
			new ParserData( $this->getTitle( $title ), $parserOutput ),
			new Subobject( $this->getTitle( $title ) )
		);
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertInstanceOf( 'SMW\SubobjectParserFunction', $instance );
	}

	/**
	 * Test instance exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructorException( $title ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new SubobjectParserFunction( $this->getTitle( $title ) );
	}

	/**
	 * Test parse()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testParse( $title, array $params, array $expected ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertEquals( $expected['errors'], $instance->parse( new ParserParameterFormatter( $params ) ) !== '' );
	}

	/**
	 * Test instantiated subobject
	 *
	 * @dataProvider getDataProvider
	 */
	public function testInstantiatedSubobject( $title, array $params, array $expected ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$instance->parse( new ParserParameterFormatter( $params ) );
		$this->assertContains( $expected['name'], $instance->getSubobject()->getName() );
	}

	/**
	 * Test instantiated property and value strings
	 *
	 * @dataProvider getDataProvider
	 */
	public function testInstantiatedPropertyValues( $title, array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$instance = $this->getInstance( $title, $parserOutput );

		// Black-box approach
		$instance->parse( new ParserParameterFormatter( $params ) );

		// Get semantic data from the ParserOutput that where stored earlier
		// during parse()
		$parserData = new ParserData( $this->getTitle( $title ), $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertCount( $expected['propertyCount'], $containerSemanticData->getProperties() );

			// Confirm added properties
			foreach ( $containerSemanticData->getProperties() as $key => $diproperty ){
				$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
				$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );

				// Confirm added property values
				foreach ( $containerSemanticData->getPropertyValues( $diproperty ) as $dataItem ){
					$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
					if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
						$this->assertContains( $dataValue->getWikiValue(), $expected['value'] );
					}
				}
			}
		}
	}
}