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
class ConceptParserFunctionTest extends ParserTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ConceptParserFunction';
	}

	/**
	 * Provides data sample, the first array contains parametrized input
	 * value while the second array contains expected return results for the
	 * instantiated object.
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			// #0
			// {{#concept: [[Modification date::+]]
			// }}
			array(
				array(
					'',
					'[[Modification date::+]]'
				),
				array(
					'result' => true,
					'propertyCount' => 1,
					'conceptQuery'  => '[[Modification date::+]]',
					'conceptDocu'   => '',
					'conceptSize'   => 1,
					'conceptDepth'  => 1,
				)
			),

			// #1
			// {{#concept: [[Modification date::+]]
			// |Foooooooo
			// }}
			array(
				array(
					'',
					'[[Modification date::+]]',
					'Foooooooo'
				),
				array(
					'result' => true,
					'propertyCount' => 1,
					'conceptQuery'  => '[[Modification date::+]]',
					'conceptDocu'   => 'Foooooooo',
					'conceptSize'   => 1,
					'conceptDepth'  => 1,
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
			array( NS_MAIN, NS_HELP, SMW_NS_CONCEPT )
		);
	}

	/**
	 * Helper method that returns a ConceptParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $parserOutput
	 *
	 * @return ConceptParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new ConceptParserFunction(
			$this->getParserData( $title, $parserOutput ) );
	}

	/**
	 * @test ConceptParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance(
			$this->getTitle( SMW_NS_CONCEPT ),
			$this->getParserOutput()
		);
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Test instance exception
	 * @test ConceptParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = $this->getInstance( $this->getTitle( SMW_NS_CONCEPT ) );
	}

	/**
	 * Test error on wrong namespace
	 * @test ConceptParserFunction::parse
	 * @dataProvider getNameSpaceDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 */
	public function testErrorOnNamespace( $namespace ) {
		$errorMessage = smwfEncodeMessages( array( wfMessage( 'smw_no_concept_namespace' )->inContentLanguage()->text() ) );

		$instance = $this->getInstance( $this->getTitle( $namespace ), $this->getParserOutput() );
		$this->assertEquals( $errorMessage, $instance->parse( array() ) );
	}

	/**
	 * Test error on double {{#concept}} use
	 * @test ConceptParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 */
	public function testErrorOnDoubleParse( array $params ) {
		$errorMessage = smwfEncodeMessages( array( wfMessage( 'smw_multiple_concepts' )->inContentLanguage()->text() ) );

		$instance = $this->getInstance( $this->getTitle( SMW_NS_CONCEPT ), $this->getParserOutput() );
 		$instance->parse( $params );

		$this->assertEquals( $errorMessage, $instance->parse( $params ) );
	}

	/**
	 * @test ConceptParserFunction::parse
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $params
	 * @param $expected
	 */
	public function testParse( array $params, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$title = $this->getTitle( SMW_NS_CONCEPT );

		// Initialize and parse
		$instance = $this->getInstance( $title, $parserOutput );
		$instance->parse( $params );

		// Re-read data from stored parserOutput
		$parserData = $this->getParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );
		$this->assertCount( $expected['propertyCount'], $parserData->getData()->getProperties() );

		// Confirm concept property
		foreach ( $parserData->getData()->getProperties() as $key => $diproperty ){
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
			$this->assertEquals( '_CONC' , $diproperty->getKey() );

			// Confirm concept property values
			foreach ( $parserData->getData()->getPropertyValues( $diproperty ) as $dataItem ){
				$this->assertEquals( $expected['conceptQuery'], $dataItem->getConceptQuery() );
				$this->assertEquals( $expected['conceptDocu'], $dataItem->getDocumentation() );
				$this->assertEquals( $expected['conceptSize'], $dataItem->getSize() );
				$this->assertEquals( $expected['conceptDepth'], $dataItem->getDepth() );
			}
		}
	}

	/**
	 * @test ConceptParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), new MockSuperUser() );
		$result = ConceptParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}
}
