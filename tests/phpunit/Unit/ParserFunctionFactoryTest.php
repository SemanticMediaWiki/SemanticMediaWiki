<?php

namespace SMW\Tests;

use SMW\DIWikiPage;
use SMW\ParserFunctionFactory;
use SMW\Tests\Utils\UtilityFactory;

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

		$parser = $this->parserFactory->newFromTitle(
			DIWikiPage::newFromText( __METHOD__ )->getTitle()
		);

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

		$parser = $this->parserFactory->newFromTitle(
			DIWikiPage::newFromText( __METHOD__ )->getTitle()
		);

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

	public function parserFunctionProvider() {

		$provider[] = array(
			'\SMW\RecurringEventsParserFunction',
			'getRecurringEventsParser'
		);

		$provider[] = array(
			'\SMW\SubobjectParserFunction',
			'getSubobjectParser'
		);

		$provider[] = array(
			'\SMW\RecurringEventsParserFunction',
			'newRecurringEventsParserFunction'
		);

		$provider[] = array(
			'\SMW\SubobjectParserFunction',
			'newSubobjectParserFunction'
		);

		$provider[] = array(
			'\SMW\AskParserFunction',
			'newAskParserFunction'
		);

		$provider[] = array(
			'\SMW\ShowParserFunction',
			'newShowParserFunction'
		);

		$provider[] = array(
			'\SMW\SetParserFunction',
			'newSetParserFunction'
		);

		$provider[] = array(
			'\SMW\ConceptParserFunction',
			'newConceptParserFunction'
		);

		$provider[] = array(
			'\SMW\DeclareParserFunction',
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
