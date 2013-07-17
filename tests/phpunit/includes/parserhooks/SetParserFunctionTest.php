<?php

namespace SMW\Test;

use SMW\SetParserFunction;
use SMW\ParserData;
use SMW\ParserParameterFormatter;
use SMW\MessageFormatter;

use SMWDIWikiPage;
use SMWDataItem;
use Title;
use ParserOutput;

/**
 * Tests for the SetParserFunction class
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
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SetParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SetParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\SetParserFunction';
	}

	/**
	 * Provides data sample normally found in connection with the {{#set}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// #0 Single data set
			// {{#set:
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar' ),
				array(
					'errors' => 0,
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Bar'
				)
			),

			// #1 Empty data set
			// {{#set:
			// |Foo=
			// }}
			array(
				array( 'Foo=' ),
				array(
					'errors' => 0,
					'propertyCount' => 0,
					'propertyLabel' => '',
					'propertyValue' => ''
				)
			),

			// #2 Multiple data set
			// {{#set:
			// |BarFoo=9001
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar', 'BarFoo=9001' ),
				array(
					'errors' => 0,
					'propertyCount' => 2,
					'propertyLabel' => array( 'Foo', 'BarFoo' ),
					'propertyValue' => array( 'Bar', '9001' )
				)
			),

			// #3 Multiple data set with an error record
			// {{#set:
			// |_Foo=9001 --> will raise an error
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar', '_Foo=9001' ),
				array(
					'errors' => 1,
					'propertyCount' => 1,
					'propertyLabel' => array( 'Foo' ),
					'propertyValue' => array( 'Bar' )
				)
			),

		);
	}

	/**
	 * Helper method that returns a SetParserFunction object
	 *
	 * @return  SetParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new SetParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new MessageFormatter( $title->getPageLanguage() )
		);
	}

	/**
	 * @test SetParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SetParserFunction::__construct (Test instance exception)
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance =  $this->getInstance( $this->getTitle() );
	}

	/**
	 * @test SetParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$result = $instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertInternalType( 'string', $result );
	}

	/**
	 * @test SetParserFunction::parse (Test instantiated property and value strings)
	 * @dataProvider getDataProvider
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

		// Re-read data from stored parserOutput
		$parserData = $this->getParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );
		$this->assertSemanticData( $parserData->getData(), $expected );
	}

	/**
	 * @test SetParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), $this->getUser() );
		$result = SetParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
