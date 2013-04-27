<?php

namespace SMW\Test;

use SMW\SubobjectParserFunction;
use SMW\Subobject;
use SMW\ParserParameterFormatter;

use SMWDIProperty;
use SMWDataItem;
use Title;
use ParserOutput;

/**
 * Tests for the SMW\SubobjectParserFunction class
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
 * @ingroup SMW
 * @ingroup ParserFunction
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMW\SubobjectParserFunction class
 */
class SubobjectParserFunctionTest extends ParserTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\SubobjectParserFunction';
	}

	/**
	 * Provides data sample normally found in connection with the {{#subobject}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function getSubobjectDataProvider() {
		// Get the right language for an error object
		$diPropertyError = new SMWDIProperty( SMWDIProperty::TYPE_ERROR );

		return array(

			// Anonymous identifier
			// {{#subobject:
			// |Foo=bar
			// }}
			array(
				array( '', 'Foo=bar' ),
				array(
					'errors' => false,
					'name' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Bar'
				)
			),

			// Anonymous identifier
			// {{#subobject:-
			// |Foo=1001 9009
			// }}
			array(
				array( '-', 'Foo=1001 9009' ),
				array(
					'errors' => false,
					'name' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => '1001 9009'
				)
			),

			// Named identifier
			// {{#subobject:FooBar
			// |FooBar=Bar foo
			// }}
			array(
				array( 'FooBar', 'FooBar=Bar foo' ),
				array(
					'errors' => false,
					'name' => 'FooBar',
					'propertyCount' => 1,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar foo'
				)
			),

			// Named identifier
			// {{#subobject:Foo bar
			// |Foo=Help:Bar
			// }}
			array(
				array( 'Foo bar', 'Foo=Help:Bar' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Help:Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// }}
			array(
				array( ' Foo bar foo ', 'Bar=foo Bar' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 1,
					'propertyLabel' => 'Bar',
					'propertyValue' => 'Foo Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |状況=超やばい
			// |Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject
			// }}
			array(
				array(
					' Foo bar foo ',
					'状況=超やばい',
					'Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ),
				array(
					'errors' => false,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( '状況', 'Bar' ),
					'propertyValue' => array( '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' )
				)
			),

			// Returns an error due to wrong declaration (see Modification date)

			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// |Modification date=foo Bar
			// }}
			array(
				array( ' Foo bar foo ', 'Modification date=foo Bar' ),
				array(
					'errors' => true,
					'name' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Modification date', $diPropertyError->getLabel() ),
					'propertyValue' => array( 'Foo Bar', 'Modification date' )
				)
			),
		);
	}

	/**
	 * Helper method that returns a SubobjectParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $parserOutput
	 *
	 * @return SubobjectParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new SubobjectParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new Subobject( $title )
		);
	}

	/**
	 * @test SubobjectParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SubobjectParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new $this->getInstance( $this->getTitle() );
	}

	/**
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$result = $instance->parse( $this->getParserParameterFormatter( $params ) );
		if ( $result !== '' ){
			$this->assertTrue( $expected['errors'] );
		} else {
			$this->assertFalse( $expected['errors'] );
		}
	}

	/**
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedSubobject( array $params, array $expected ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$instance->parse( $this->getParserParameterFormatter( $params ) );
		$this->assertContains( $expected['name'], $instance->getSubobject()->getName() );
	}

	/**
	 * Test instantiated property and value strings
	 *
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedPropertyValues( array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$title = $this->getTitle();

		// Initialize and parse
		$instance = $this->getInstance( $title, $parserOutput );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// Get semantic data from the ParserOutput
		$parserData = $this->getParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertSemanticData( $containerSemanticData, $expected );
		}
	}

	/**
	 * @test SubobjectParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), new MockSuperUser() );
		$result = SubobjectParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
