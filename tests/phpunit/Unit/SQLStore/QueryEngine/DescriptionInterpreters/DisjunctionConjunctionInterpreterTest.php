<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\DisjunctionConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class DisjunctionConjunctionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $conditionBuilder;
	private $querySegmentValidator;
	private $descriptionFactory;

	protected function setUp() {
		parent::setUp();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DisjunctionConjunctionInterpreter::class,
			new DisjunctionConjunctionInterpreter( $this->conditionBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testInterpretDescription( $description, $expected ) {

		$queryEngineFactory = new QueryEngineFactory(
			$this->store
		);

		$instance = new DisjunctionConjunctionInterpreter(
			$queryEngineFactory->newconditionBuilder()
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
		$expectedDisjunction->components = [ 1 => true, 2 => true ];

		$provider[] = [
			$description,
			$expectedDisjunction
		];

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
		$expectedConjunction->components = [ 1 => true, 2 => true ];

		$provider[] = [
			$description,
			$expectedConjunction
		];

		#2 No query
		$description = $descriptionFactory->newConjunction();

		$description->addDescription(
			$descriptionFactory->newThingDescription()
		);

		$expectedConjunction = new \stdClass;
		$expectedConjunction->type = 0;
		$expectedConjunction->components = [];

		$provider[] = [
			$description,
			$expectedConjunction
		];

		return $provider;
	}

}
