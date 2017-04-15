<?php

namespace SMW\Tests;

use SMW\DIWikiPage;
use SMW\ParserFunctionFactory;
use SMW\Tests\TestEnvironment;

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
			call_user_func_array( array( $parserFunctionFactory, $method ), array( $parser ) )
		);
	}

	/**
	 * @dataProvider parserFunctionDefinitionProvider
	 */
	public function testParserFunctionDefinition( $method, $expected ) {

		$parser = $this->parserFactory->create( __METHOD__ );

		$parserFunctionFactory = new ParserFunctionFactory( $parser );

		$definition = call_user_func_array(
			array( $parserFunctionFactory, $method ),
			array( '' )
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

		$this->parserData->expects( $this->once() )
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

		$provider[] = array(
			'\SMW\ParserFunctions\RecurringEventsParserFunction',
			'getRecurringEventsParser'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\SubobjectParserFunction',
			'getSubobjectParser'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\RecurringEventsParserFunction',
			'newRecurringEventsParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\SubobjectParserFunction',
			'newSubobjectParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\AskParserFunction',
			'newAskParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\ShowParserFunction',
			'newShowParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\SetParserFunction',
			'newSetParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\ConceptParserFunction',
			'newConceptParserFunction'
		);

		$provider[] = array(
			'\SMW\ParserFunctions\DeclareParserFunction',
			'newDeclareParserFunction'
		);

		return $provider;
	}

	public function parserFunctionDefinitionProvider() {

		$provider[] = array(
			'newAskParserFunctionDefinition',
			'ask'
		);

		$provider[] = array(
			'newShowParserFunctionDefinition',
			'show'
		);

		$provider[] = array(
			'newSubobjectParserFunctionDefinition',
			'subobject'
		);

		$provider[] = array(
			'newRecurringEventsParserFunctionDefinition',
			'set_recurring_event'
		);

		$provider[] = array(
			'newSetParserFunctionDefinition',
			'set'
		);

		$provider[] = array(
			'newConceptParserFunctionDefinition',
			'concept'
		);

		$provider[] = array(
			'newDeclareParserFunctionDefinition',
			'declare'
		);

		return $provider;
	}

}
