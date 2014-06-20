<?php

namespace SMW\Tests;

use SMW\Tests\Util\ParserFactory;

use SMW\ConceptParserFunction;
use SMW\MessageFormatter;
use SMW\ParserData;

use Title;
use ParserOutput;

/**
 * @covers \SMW\ConceptParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ConceptParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ConceptParserFunction',
			$this->newInstance()
		);
	}

	/**
	 * @dataProvider namespaceDataProvider
	 */
	public function testErrorOnNamespace( $namespace ) {

		$title = Title::newFromText( __METHOD__, $namespace );

		$instance = $this->newInstance( $title, new ParserOutput() );

		$this->assertEquals(
			$this->getMessageText( $title, 'smw_no_concept_namespace' ),
			$instance->parse( array() )
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testErrorOnDoubleParse( array $params ) {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$instance = $this->newInstance( $title, new ParserOutput() );
 		$instance->parse( $params );

		$instance->parse( $params );

		$this->assertEquals(
			$this->getMessageText( $title, 'smw_multiple_concepts' ),
			$instance->parse( $params )
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testParse( array $params, array $expected ) {

		$parserOutput =  new ParserOutput();
		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $params );

		$parserData = new ParserData( $title, $parserOutput );

		$this->assertCount(
			$expected['propertyCount'],
			$parserData->getSemanticData()->getProperties()
		);

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

	public function testStaticRender() {

		$parser = ParserFactory::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->assertInternalType(
			'string',
			ConceptParserFunction::render( $parser )
		);
	}

	/**
	 * @return array
	 */
	public function queryParameterProvider() {

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
				ParserFactory::newFromTitle( Title::newFromText( __METHOD__ ) ),
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

	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );
		}

		if ( $parserOutput === null ) {
			$parserOutput = new ParserOutput();
		}

		return new ConceptParserFunction(
			new ParserData( $title, $parserOutput ),
			new MessageFormatter( $title->getPageLanguage() )
		);
	}

	private function getMessageText( Title $title, $error ) {
		$message = new MessageFormatter( $title->getPageLanguage() );
		return $message->addFromKey( $error )->getHtml();
	}

}
