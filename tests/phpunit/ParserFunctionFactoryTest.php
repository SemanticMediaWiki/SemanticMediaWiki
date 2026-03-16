<?php

namespace SMW\Tests;

use MediaWiki\Parser\Parser;
use PHPUnit\Framework\TestCase;
use SMW\ParserData;
use SMW\ParserFunctionFactory;
use SMW\ParserFunctions\AskParserFunction;
use SMW\ParserFunctions\ConceptParserFunction;
use SMW\ParserFunctions\DeclareParserFunction;
use SMW\ParserFunctions\RecurringEventsParserFunction;
use SMW\ParserFunctions\SetParserFunction;
use SMW\ParserFunctions\ShowParserFunction;
use SMW\ParserFunctions\SubobjectParserFunction;

/**
 * @covers \SMW\ParserFunctionFactory
 * @group smenatic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParserFunctionFactoryTest extends TestCase {

	private $testEnvironment;

	private $parserData;

	private $parserFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'ParserData', $this->parserData );

		$this->parserFactory = $this->testEnvironment->getUtilityFactory()->newParserFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParserFunctionFactory::class,
			new ParserFunctionFactory( $parser )
		);

		$this->assertInstanceOf(
			ParserFunctionFactory::class,
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

		$this->assertIsInt(

			$definition[2]
		);
	}

	public function parserFunctionProvider() {
		$provider[] = [
			RecurringEventsParserFunction::class,
			'getRecurringEventsParser'
		];

		$provider[] = [
			SubobjectParserFunction::class,
			'getSubobjectParser'
		];

		$provider[] = [
			RecurringEventsParserFunction::class,
			'newRecurringEventsParserFunction'
		];

		$provider[] = [
			SubobjectParserFunction::class,
			'newSubobjectParserFunction'
		];

		$provider[] = [
			AskParserFunction::class,
			'newAskParserFunction'
		];

		$provider[] = [
			ShowParserFunction::class,
			'newShowParserFunction'
		];

		$provider[] = [
			SetParserFunction::class,
			'newSetParserFunction'
		];

		$provider[] = [
			ConceptParserFunction::class,
			'newConceptParserFunction'
		];

		$provider[] = [
			DeclareParserFunction::class,
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
