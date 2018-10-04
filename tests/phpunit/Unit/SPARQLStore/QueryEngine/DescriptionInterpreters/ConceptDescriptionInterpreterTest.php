<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\ApplicationFactory;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ConceptDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $descriptionInterpreterFactory;

	protected function setUp() {
		parent::setUp();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter',
			new ConceptDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ConceptDescription' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConceptDescriptionInterpreter( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setCircularReferenceGuard( $circularReferenceGuard );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function testConceptDescriptionInterpreterForAnyValueConceptUsingMockedStore() {

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( new DIProperty( '_CONC' ) ) )
			->will( $this->returnValue( [
				new DIConcept( '[[Foo::+]]', 'Bar', 1, 0, 0 ) ] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->with( $this->equalTo( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) ) )
			->will( $this->returnValue( $semanticData ) );

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$description = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );
		$orderByProperty = null;
		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setCircularReferenceGuard( $circularReferenceGuard );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$expectedConditionString = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->getString();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function descriptionProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		];

		return $provider;
	}

}
