<?php

namespace SMW\Tests\Unit\SPARQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Concept;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Query\Language\ConceptDescription;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Store;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Utils\CircularReferenceGuard;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends TestCase {

	private $applicationFactory;
	private $descriptionInterpreterFactory;

	protected function setUp(): void {
		parent::setUp();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	protected function tearDown(): void {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ConceptDescriptionInterpreter::class,
			new ConceptDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( ConceptDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$circularReferenceGuard = $this->getMockBuilder( CircularReferenceGuard::class )
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
		$circularReferenceGuard = $this->getMockBuilder( CircularReferenceGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( new Property( '_CONC' ) )
			->willReturn( [
				new Concept( '[[Foo::+]]', 'Bar', 1, 0, 0 ) ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->with( new WikiPage( 'Foo', SMW_NS_CONCEPT ) )
			->willReturn( $semanticData );

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$description = new ConceptDescription( new WikiPage( 'Foo', SMW_NS_CONCEPT ) );
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
			WhereCondition::class,
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
		$conditionType = FalseCondition::class;

		$description = new ConceptDescription( new WikiPage( 'Foo', SMW_NS_CONCEPT ) );
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
