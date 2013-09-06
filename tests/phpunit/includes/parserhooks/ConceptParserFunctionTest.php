<?php

namespace SMW\Test;

use SMW\ConceptParserFunction;
use SMW\MessageFormatter;
use SMW\ParserData;

use Title;
use ParserOutput;

/**
 * Tests for the ConceptParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ConceptParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ConceptParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ConceptParserFunction';
	}

	/**
	 * Helper method that returns a instance
	 *
	 * @return ConceptParserFunction
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle( SMW_NS_CONCEPT );
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		return new ConceptParserFunction(
			$this->newParserData( $title, $parserOutput ),
			new MessageFormatter( $title->getPageLanguage() )
		);
	}

	/**
	 * Helper method that returns a text
	 *
	 * @return string
	 */
	private function getMessageText( Title $title, $error ) {
		$message = new MessageFormatter( $title->getPageLanguage() );
		return $message->addFromKey( $error )->getHtml();
	}

	/**
	 * @test ConceptParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test ConceptParserFunction::parse (Test error on wrong namespace)
	 * @dataProvider namespaceDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 */
	public function testErrorOnNamespace( $namespace ) {

		$title = $this->newTitle( $namespace );
		$instance = $this->newInstance( $title, $this->newParserOutput() );

		$this->assertEquals(
			$this->getMessageText( $title, 'smw_no_concept_namespace' ),
			$instance->parse( array() ),
			'asserts that an error is raised due to a wrong namespace'
		);

	}

	/**
	 * @test ConceptParserFunction::parse (Test error on double {{#concept}} use)
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $params
	 */
	public function testErrorOnDoubleParse( array $params ) {

		$title = $this->newTitle( SMW_NS_CONCEPT );

		$instance = $this->newInstance( $title, $this->newParserOutput() );
 		$instance->parse( $params );

		// First call
		$instance->parse( $params );

		$this->assertEquals(
			$this->getMessageText( $title, 'smw_multiple_concepts' ),
			$instance->parse( $params ),
			'assert that the second call raises an error'
		);
	}

	/**
	 * @test ConceptParserFunction::parse
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $params
	 * @param $expected
	 */
	public function testParse( array $params, array $expected ) {

		$parserOutput =  $this->newParserOutput();
		$title = $this->newTitle( SMW_NS_CONCEPT );

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $params );

		// Re-read data from stored parserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$parserData->getData(),
			'assert that the returning instance if of type SemanticData'
		);

		$this->assertCount(
			$expected['propertyCount'],
			$parserData->getData()->getProperties(),
			'asserts the expected amount of properties available through getProperties()'
		);

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

		$parser = $this->newParser( $this->newTitle(), $this->newMockUser() );
		$result = ConceptParserFunction::render( $parser );

		$this->assertInternalType(
			'string',
			$result,
			'asserts that the returning result is always of type string'
		);
	}

	/**
	 * Provides data sample, the first array contains parametrized input
	 * value while the second array contains expected return results for the
	 * instantiated object.
	 *
	 * @return array
	 */
	public function queryDataProvider() {

		$provider = array();

		// #0
		// {{#concept: [[Modification date::+]]
		// }}
		$provider[] = array(
			array(
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
		);

		// #1
		// {{#concept: [[Modification date::+]]
		// |Foooooooo
		// }}
		$provider[] = array(
			array(
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
		);

		// #2 (includes Parser object)
		$provider[] = array(
			array(
				$this->newParser( $this->newTitle(), $this->newMockUser() ),
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
		);

		return $provider;

	}

	/**
	 * NameSpaceDataProvider
	 *
	 * @return array
	 */
	public function namespaceDataProvider() {
		return array(
			array( NS_MAIN ),
			array( NS_HELP )
		);
	}

}
