<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\ParserFunctions\ConceptParserFunction;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @covers \SMW\ParserFunctions\ConceptParserFunction
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ConceptParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\ConceptParserFunction',
			new ConceptParserFunction( $parserData, $messageFormatter )
		);
	}

	/**
	 * @dataProvider namespaceDataProvider
	 */
	public function testErrorForNonConceptNamespace( $namespace ) {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->once() )
			->method( 'addFromKey' )
			->with( $this->equalTo( 'smw_no_concept_namespace' ) )
			->will( $this->returnSelf() );

		$instance = new ConceptParserFunction( $parserData, $messageFormatter );
		$instance->parse( [] );
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testErrorForOnDoubleParse( array $params ) {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__, SMW_NS_CONCEPT ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->will( $this->returnSelf() );

		$messageFormatter->expects( $this->once() )
			->method( 'addFromKey' )
			->with( $this->equalTo( 'smw_multiple_concepts' ) )
			->will( $this->returnSelf() );

		$instance = new ConceptParserFunction( $parserData, $messageFormatter );

		$instance->parse( $params );
		$instance->parse( $params );
	}

	public function testExistForFoundMessageFormatterEntry() {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__, SMW_NS_CONCEPT ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->will( $this->returnSelf() );

		$messageFormatter->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$messageFormatter->expects( $this->once() )
			->method( 'getHtml' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new ConceptParserFunction( $parserData, $messageFormatter );

		$this->assertEquals(
			'Foo',
			$instance->parse( [] )
		);
	}

	/**
	 * @dataProvider queryParameterProvider
	 */
	public function testParse( array $params, array $expected ) {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__, SMW_NS_CONCEPT ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromArray' )
			->will( $this->returnSelf() );

		$instance = new ConceptParserFunction( $parserData, $messageFormatter );
		$instance->parse( $params );

		$this->assertCount(
			$expected['propertyCount'],
			$parserData->getSemanticData()->getProperties()
		);

		foreach ( $parserData->getSemanticData()->getProperties() as $property ){

			if ( $property->getKey() !== '_CONC' ) {
				continue;
			}

			foreach ( $parserData->getSemanticData()->getPropertyValues( $property ) as $dataItem ) {
				$this->assertEquals( $expected['conceptQuery'], $dataItem->getConceptQuery() );
				$this->assertEquals( $expected['conceptDocu'], $dataItem->getDocumentation() );
				$this->assertEquals( $expected['conceptSize'], $dataItem->getSize() );
				$this->assertEquals( $expected['conceptDepth'], $dataItem->getDepth() );
			}
		}
	}

	public function queryParameterProvider() {

		$provider = [];

		// #0
		// {{#concept: [[Modification date::+]]
		// }}
		$provider[] = [
			[
				'[[Modification date::+]]'
			],
			[
				'result' => true,
				'propertyCount' => 2,
				'conceptQuery'  => '[[Modification date::+]]',
				'conceptDocu'   => '',
				'conceptSize'   => 1,
				'conceptDepth'  => 1,
			]
		];

		// #1
		// {{#concept: [[Modification date::+]]
		// |Foooooooo
		// }}
		$provider[] = [
			[
				'[[Modification date::+]]',
				'Foooooooo'
			],
			[
				'result' => true,
				'propertyCount' => 2,
				'conceptQuery'  => '[[Modification date::+]]',
				'conceptDocu'   => 'Foooooooo',
				'conceptSize'   => 1,
				'conceptDepth'  => 1,
			]
		];

		// #2 (includes Parser object)
		$parser = UtilityFactory::getInstance()->newParserFactory()->newFromTitle( Title::newFromText( __METHOD__ ) );

		$provider[] = [
			[
				$parser,
				'[[Modification date::+]]',
				'Foooooooo'
			],
			[
				'result' => true,
				'propertyCount' => 2,
				'conceptQuery'  => '[[Modification date::+]]',
				'conceptDocu'   => 'Foooooooo',
				'conceptSize'   => 1,
				'conceptDepth'  => 1,
			]
		];

		return $provider;

	}

	public function namespaceDataProvider() {
		return [
			[ NS_MAIN ],
			[ NS_HELP ]
		];
	}

}
