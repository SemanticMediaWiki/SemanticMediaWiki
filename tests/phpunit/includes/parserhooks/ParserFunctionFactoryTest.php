<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;

use SMW\ParserFunctionFactory;

use Title;

/**
 * @covers \SMW\ParserFunctionFactory
 *
 * @group SMW
 * @group SMWExtension
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

		$this->parserFactory = UtilityFactory::getInstance()->newParserFactory();
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

		$parser = $this->parserFactory->newFromTitle( Title::newFromText( __METHOD__ ) );
		$parserFunctionFactory = new ParserFunctionFactory( $parser );

		$this->assertInstanceOf(
			$instance,
			call_user_func_array( array( $parserFunctionFactory, $method ), array() )
		);
	}

	public function parserFunctionProvider() {

		$provider[] = array( '\SMW\RecurringEventsParserFunction', 'getRecurringEventsParser' );
		$provider[] = array( '\SMW\SubobjectParserFunction',       'getSubobjectParser' );

		$provider[] = array( '\SMW\RecurringEventsParserFunction', 'newRecurringEventsParserFunction' );
		$provider[] = array( '\SMW\SubobjectParserFunction',       'newSubobjectParserFunction' );

		$provider[] = array( '\SMW\AskParserFunction', 'newAskParserFunction' );
		$provider[] = array( '\SMW\ShowParserFunction',  'newShowParserFunction' );

		$provider[] = array( '\SMW\SetParserFunction', 'newSetParserFunction' );
		$provider[] = array( '\SMW\ConceptParserFunction', 'newConceptParserFunction' );
		$provider[] = array( '\SMW\DeclareParserFunction', 'newDeclareParserFunction' );

		return $provider;
	}

}
