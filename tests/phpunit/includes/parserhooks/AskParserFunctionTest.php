<?php

namespace SMW\Test;

use SMW\AskParserFunction;
use SMW\QueryData;

use Title;
use ParserOutput;

/**
 * Tests for the SMW\AskParserFunction class
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
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMW\AskParserFunction class
 *
 * @ingroup SMW
 * @ingroup Test
 */
class AskParserFunctionTest extends ParserTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\AskParserFunction';
	}

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
				array(
					'',
					'[[Modification date::+]]',
					'?Modification date',
					'format=list'
				),
				array(
					'result' => false,
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
				)
			),

			// #1 Query string with spaces
			// {{#ask: [[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				array(
					'',
					'[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]',
					'?Modification date',
					'?Has title',
					'format=list'
				),
				array(
					'result' => false,
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'list', 4, 1, '[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]' )
				)
			),

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=list'
				),
				array(
					'result' => false,
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

			// Unknown format, default table

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=bar
			// }}
			array(
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=bar'
				),
				array(
					'result' => false,
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),
		);
	}

	/**
	 * Helper method that returns a AskParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $parserOutput
	 *
	 * @return AskParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new AskParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new QueryData( $title )
		);
	}

	/**
	 * @test AskParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test AskParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new $this->getInstance( $this->getTitle() );
	}

	/**
	 * @test AskParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$result = $instance->parse( $params );
		$this->assertInternalType( 'string', $result );
	}

	/**
	 * Test ($GLOBALS['smwgQEnabled'] = false)
	 *
	 * @test AskParserFunction::parse
	 *
	 * @since 1.9
	 */
	public function testParseDisabledsmwgQEnabled() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$expected = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		$result = $instance->parse( array(), false );
		$this->assertEquals( $expected , $result );
	}

	/**
	 * @test AskParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedQueryData( array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$title = $this->getTitle();

		// Initialize and parse
		$instance = $this->getInstance( $title, $parserOutput );
		$instance->parse( $params, true );

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
	 * @test AskParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), new MockSuperUser() );
		$result = AskParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
