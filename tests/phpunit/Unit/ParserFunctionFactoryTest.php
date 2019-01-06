<?php

namespace SMW\Tests;

use SMW\ParserFunctionFactory;

/**
 * @covers \SMW\ParserFunctionFactory
 * @group smenatic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserFunctionFactoryTest extends \PHPUnit_Framework_TestCase {

	private $parserFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'ParserData', $this->parserData );

		$this->parserFactory = $this->testEnvironment->getUtilityFactory()->newParserFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctionFactory',
			new ParserFunctionFactory( $parser )
		);

		$this->assertInstanceOf(
			'\SMW\ParserFunctionFactory',
			ParserFunctionFactory::newFromParser( $parser )
		);
	}

	/**
	 * @dataProvider parserFunctionProvider
	 */
	public function testParserFunctionInstance( $instance, $method ) {

		$parser = $this->parserFactory->create( __METHOD__ );

		$parserFunctionFactory = new ParserFunctionFactory( $parser );

		$this->assertInstanceOf(
			$instance,
			call_user_func_array( [ $parserFunctionFactory, $method ], [ $parser ] )
		);
	}

	/**
	 * @dataProvider parserFunctionDefinitionProvider
	 */
	public function testParserFunctionDefinition( $method, $expected ) {

		$parser = $this->parserFactory->create( __METHOD__ );

		$parserFunctionFactory = new ParserFunctionFactory( $parser );

		$definition = call_user_func_array(
			[ $parserFunctionFactory, $method ],
			[ '' ]
		);

		$this->assertEquals(
			$expected,
			$definition[0]
		);

		$this->assertInstanceOf(
			'\Closure',
			$definition[1]
		);

		$this->assertInternalType(
			'integer',
			$definition[2]
		);
	}

	public function testAskParserFunctionWithParserOption() {

		$this->parserData->expects( $this->at( 0 ) )
			->method( 'setOption' )
			->with(
				$this->equalTo( \SMW\ParserData::NO_QUERY_DEPENDENCY_TRACE ),
				$this->anything() );

		$parser = $this->parserFactory->create( __METHOD__ );
		$parser->getOptions()->smwAskNoDependencyTracking = true;

		$parserFunctionFactory = new ParserFunctionFactory();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\AskParserFunction',
			$parserFunctionFactory->newAskParserFunction( $parser )
		);
	}

	public function parserFunctionProvider() {

		$provider[] = [
			'\SMW\ParserFunctions\RecurringEventsParserFunction',
			'getRecurringEventsParser'
		];

		$provider[] = [
			'\SMW\ParserFunctions\SubobjectParserFunction',
			'getSubobjectParser'
		];

		$provider[] = [
			'\SMW\ParserFunctions\RecurringEventsParserFunction',
			'newRecurringEventsParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\SubobjectParserFunction',
			'newSubobjectParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\AskParserFunction',
			'newAskParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\ShowParserFunction',
			'newShowParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\SetParserFunction',
			'newSetParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\ConceptParserFunction',
			'newConceptParserFunction'
		];

		$provider[] = [
			'\SMW\ParserFunctions\DeclareParserFunction',
			'newDeclareParserFunction'
		];

		return $provider;
	}

	public function parserFunctionDefinitionProvider() {

		$provider[] = [
			'getAskParserFunctionDefinition',
			'ask'
		];

		$provider[] = [
			'getShowParserFunctionDefinition',
			'show'
		];

		$provider[] = [
			'getSubobjectParserFunctionDefinition',
			'subobject'
		];

		$provider[] = [
			'getSetRecurringEventParserFunctionDefinition',
			'set_recurring_event'
		];

		$provider[] = [
			'getSetParserFunctionDefinition',
			'set'
		];

		$provider[] = [
			'getConceptParserFunctionDefinition',
			'concept'
		];

		$provider[] = [
			'getDeclareParserFunctionDefinition',
			'declare'
		];

		return $provider;
	}

}
