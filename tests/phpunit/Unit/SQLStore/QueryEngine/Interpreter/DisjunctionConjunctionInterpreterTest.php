<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\Interpreter\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\DisjunctionConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class DisjunctionConjunctionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;
	private $descriptionFactory;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\DisjunctionConjunctionInterpreter',
			new DisjunctionConjunctionInterpreter( $querySegmentListBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testInterpretDescription( $description, $expected ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$queryEngineFactory = new QueryEngineFactory( $store );

		$instance = new DisjunctionConjunctionInterpreter(
			$queryEngineFactory->newQuerySegmentListBuilder()
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function descriptionProvider() {

		$descriptionFactory = new DescriptionFactory();

		#0 Disjunction
		$description = $descriptionFactory->newDisjunction();

		$description->addDescription(
			$descriptionFactory->newNamespaceDescription( NS_HELP )
		);

		$description->addDescription(
			$descriptionFactory->newNamespaceDescription( NS_MAIN )
		);

		$expectedDisjunction = new \stdClass;
		$expectedDisjunction->type = 3;
		$expectedDisjunction->components = array( 1 => true, 2 => true );

		$provider[] = array(
			$description,
			$expectedDisjunction
		);

		#1 Conjunction
		$description = $descriptionFactory->newConjunction();

		$description->addDescription(
			$descriptionFactory->newNamespaceDescription( NS_HELP )
		);

		$description->addDescription(
			$descriptionFactory->newNamespaceDescription( NS_MAIN )
		);

		$expectedConjunction = new \stdClass;
		$expectedConjunction->type = 4;
		$expectedConjunction->components = array( 1 => true, 2 => true );

		$provider[] = array(
			$description,
			$expectedConjunction
		);

		#2 No query
		$description = $descriptionFactory->newConjunction();

		$description->addDescription(
			$descriptionFactory->newThingDescription()
		);

		$expectedConjunction = new \stdClass;
		$expectedConjunction->type = 0;
		$expectedConjunction->components = array();

		$provider[] = array(
			$description,
			$expectedConjunction
		);

		return $provider;
	}

}
