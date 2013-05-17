<?php

namespace SMW\Test;

use SMW\AskParserFunction;
use SMW\QueryData;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * Tests for the AskParserFunction class
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
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the AskParserFunction class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class AskParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\AskParserFunction';
	}

	/**
	 * Provides sample data usually found in {{#ask}} queries
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			// #0
			// {{#ask: [[Modification date::+]]
			// |?Modification date
			// |format=list
			// }}
			array(
				array(
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

			// #2
			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				array(
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

			// #3 Known format
			// {{#ask: [[File:Fooo]]
			// |?Modification date
			// |default=no results
			// |format=feed
			// }}
			array(
				array(
					'[[File:Fooo]]',
					'?Modification date',
					'default=no results',
					'format=feed'
				),
				array(
					'result' => false,
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'feed', 1, 1, '[[:File:Fooo]]' )
				)
			),

			// #4 Unknown format, default table
			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=bar
			// }}
			array(
				array(
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=lula'
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
	 * @param Title $title
	 * @param ParserOutput $parserOutput
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
	 * @test AskParserFunction::parse (Test ($GLOBALS['smwgQEnabled'] = false))
	 *
	 * @since 1.9
	 */
	public function testParseDisabledsmwgQEnabled() {
		$expected = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );

		// Make protected method accessible
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'disabled' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance );
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
		$parser = $this->getParser( $this->getTitle(), $this->getUser() );
		$result = AskParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
