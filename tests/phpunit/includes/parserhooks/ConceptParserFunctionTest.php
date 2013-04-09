<?php

namespace SMW\Test;

use SMW\ConceptParserFunction;
use SMW\ParserData;

use Title;
use ParserOutput;

/**
 * Tests for the SMW\ConceptParserFunction class
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
class ConceptParserFunctionTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// {{#concept: [[Modification date::+]]
			// }}
			array(
				'Concept:Foo',
				array(
					'',
					'[[Modification date::+]]'
				),
				array(
					'result' => true,
					'propertyCount' => 1,
					'conceptQuery' => '[[Modification date::+]]',
					'conceptDocu' => '',
					'conceptSize' => 1,
					'conceptDepth' => 1,
				)
			),

			// {{#concept: [[Modification date::+]]
			// |Foooooooo
			// }}
			array(
				'Concept:Bar',
				array(
					'',
					'[[Modification date::+]]',
					'Foooooooo'
				),
				array(
					'result' => true,
					'propertyCount' => 1,
					'conceptQuery' => '[[Modification date::+]]',
					'conceptDocu' => 'Foooooooo',
					'conceptSize' => 1,
					'conceptDepth' => 1,
				)
			)
		);
	}

	/**
	 * NameSpaceDataProvider
	 *
	 * @return array
	 */
	public function getNameSpaceDataProvider() {
		return array(
			array( 'Foo', 'Help:252', 'Concepts:Bar' )
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
	 * @return SMW\ConceptParserFunction
	 */
	private function getInstance( $title, $parserOutput = '' ) {
		return new ConceptParserFunction(
			new ParserData( $this->getTitle( $title ), $parserOutput ) );
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertInstanceOf( 'SMW\ConceptParserFunction', $instance );
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
	 * Test error on wrong namespace
	 *
	 * @dataProvider getNameSpaceDataProvider
	 */
	public function testErrorOnNamespace( $title ) {
		$errorMessage = smwfEncodeMessages( array( wfMessage( 'smw_no_concept_namespace' )->inContentLanguage()->text() ) );

		$instance = $this->getInstance( $title, $this->getParserOutput() );
		$this->assertEquals( $errorMessage, $instance->parse( array() ) );
	}

	/**
	 * Test error on double {{#concept}} use
	 *
	 * @dataProvider getDataProvider
	 */
	public function testErrorOnDoubleParse( $title, array $params ) {
		$errorMessage = smwfEncodeMessages( array( wfMessage( 'smw_multiple_concepts' )->inContentLanguage()->text() ) );

		$instance = $this->getInstance( $title, $this->getParserOutput() );
 		$instance->parse( $params );

		$this->assertEquals( $errorMessage, $instance->parse( $params ) );
	}

	/**
	 * Test instantiated property and value
	 *
	 * @dataProvider getDataProvider
	 */
	public function testSemanticData( $title, array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$instance = $this->getInstance( $title, $parserOutput );

		// Black-box
		$instance->parse( $params );

		// Re-read data from stored parserOutput
		$parserData = new ParserData( $this->getTitle( $title ), $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getSemanticData() );
		$this->assertCount( $expected['propertyCount'], $parserData->getSemanticData()->getProperties() );

		// Confirm concept property
		foreach ( $parserData->getSemanticData()->getProperties() as $key => $diproperty ){
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
			$this->assertEquals( '_CONC' , $diproperty->getKey() );

			// Confirm concept property values
			foreach ( $parserData->getSemanticData()->getPropertyValues( $diproperty ) as $dataItem ){
				$this->assertEquals( $expected['conceptQuery'], $dataItem->getConceptQuery() );
				$this->assertEquals( $expected['conceptDocu'], $dataItem->getDocumentation() );
				$this->assertEquals( $expected['conceptSize'], $dataItem->getSize() );
				$this->assertEquals( $expected['conceptDepth'], $dataItem->getDepth() );
			}
		}
	}
}
