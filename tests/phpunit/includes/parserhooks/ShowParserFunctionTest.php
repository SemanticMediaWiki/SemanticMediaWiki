<?php

namespace SMW\Test;

use SMW\ShowParserFunction;
use SMW\QueryData;

use Title;
use ParserOutput;

/**
 * Tests for the SMW\ShowParserFunction class
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
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMW\ShowParserFunction class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ShowParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ShowParserFunction';
	}

	/**
	 * Provides data sample normally found in connection with the {{#show}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// {{#show: Foo
			// |?Modification date
			// }}
			array(
				array(
					'',
					'Foo',
					'?Modification date',
				),
				array(
					'output' => '',
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
					'propertyValue' => array( 'list', 0, 1, '[[:Foo]]' )
				)
			),

			// {{#show: Help:Bar
			// |?Modification date
			// |default=no results
			// }}
			array(
				array(
					'',
					'Help:Bar',
					'?Modification date',
					'default=no results'
				),
				array(
					'output' => 'no results',
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
					'propertyValue' => array( 'list', 0, 1, '[[:Help:Bar]]' )
				)
			)
		);
	}

	/**
	 * Helper method that returns a ShowParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $parserOutput
	 * @return SMW\ShowParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new ShowParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new QueryData( $title )
		 );
	}

	/**
	 * @test ShowParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test ShowParserFunction::__construct (Test instance exception)
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance =  $this->getInstance( $this->getTitle() );
	}

	/**
	 * @test ShowParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$result = $instance->parse( $params, true );
		$this->assertEquals( $expected['output'], $result );
	}

	/**
	 * @test ShowParserFunction::parse (Test $GLOBALS['smwgQEnabled'] = false)
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 */
	public function testParseDisabledsmwgQEnabled( array $params ) {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$expected = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		$result = $instance->parse( $params, false );

		$this->assertEquals( $expected , $result );
	}

	/**
	 * @test ShowParserFunction::parse (Test generated query data)
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
		$instance->parse( $params, true, true);

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
	 * @test ShowParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), new MockSuperUser() );
		$result = ShowParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
