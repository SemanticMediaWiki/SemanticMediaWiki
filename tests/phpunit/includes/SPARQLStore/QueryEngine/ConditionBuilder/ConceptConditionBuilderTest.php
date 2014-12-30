<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Query\Language\ConceptDescription;

use SMW\DIWikiPage;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

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
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder',
			new ConceptConditionBuilder( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ConceptDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptConditionBuilder( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canBuildConditionFor( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConceptConditionBuilder( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );

		$instance = new ConceptConditionBuilder();
		$instance->setCompoundConditionBuilder( $compoundConditionBuilder );

		$condition = $instance->buildCondition( $description, $resultVariable, $orderByProperty );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function testConceptConditionBuilderForAnyValueConceptUsingMockedStore() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( new DIProperty( '_CONC' ) ) )
			->will( $this->returnValue( array(
				new DIConcept( '[[Foo::+]]' , 'Bar', 1, 0, 0 ) ) ) );

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

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );

		$instance = new ConceptConditionBuilder();
		$instance->setCompoundConditionBuilder( $compoundConditionBuilder );

		$condition = $instance->buildCondition( $description, $resultVariable, $orderByProperty );

		$expectedConditionString = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->getString();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$compoundConditionBuilder->convertConditionToString( $condition )
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

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		return $provider;
	}

}
