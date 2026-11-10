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
use SMW\Utils\ConceptExpansionGuard;

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
	private int $expansionProbe = 0;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

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
			new ConceptDescriptionInterpreter( $conditionBuilder, new ConceptExpansionGuard( ConceptExpansionGuard::DEFAULT_MAX_EXPANSIONS ) )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( ConceptDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder, new ConceptExpansionGuard( ConceptExpansionGuard::DEFAULT_MAX_EXPANSIONS ) );

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

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder, new ConceptExpansionGuard( ConceptExpansionGuard::DEFAULT_MAX_EXPANSIONS ) );

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

		$instance = new ConceptDescriptionInterpreter( $conditionBuilder, new ConceptExpansionGuard( ConceptExpansionGuard::DEFAULT_MAX_EXPANSIONS ) );

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

	private function newCountingConditionBuilder(): ConditionBuilder {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( function () {
				$this->expansionProbe++;
				return [ new Concept( '[[Foo::+]]', 'Bar', 1, 0, 0 ) ];
			} );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->applicationFactory->registerObject( 'Store', $store );

		$circularReferenceGuard = $this->getMockBuilder( CircularReferenceGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getJoinVariable', 'getOrderByProperty', 'getCircularReferenceGuard',
				'setJoinVariable', 'setOrderByProperty', 'mapDescriptionToCondition', 'addError' ] )
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getCircularReferenceGuard' )
			->willReturn( $circularReferenceGuard );

		return $conditionBuilder;
	}

	public function testInterpretDescriptionBoundsConceptFanOut() {
		// A budget of 4, with a harness that would otherwise expand 10 concepts.
		// The circular reference guard is stubbed to never fire, so only the
		// per-build expansion budget can stop the fan-out.
		$maxExpansions = 4;
		$harnessDepthLimit = 10;

		$this->expansionProbe = 0;
		$conditionBuilder = $this->newCountingConditionBuilder();

		$instance = new ConceptDescriptionInterpreter(
			$conditionBuilder,
			new ConceptExpansionGuard( $maxExpansions )
		);

		// Each mapped condition recurses into a fresh concept, up to the harness
		// limit, so nothing but the budget bounds the expansion.
		$conditionBuilder->method( 'mapDescriptionToCondition' )
			->willReturnCallback( function () use ( $instance, $harnessDepthLimit ) {
				if ( $this->expansionProbe < $harnessDepthLimit ) {
					$instance->interpretDescription(
						new ConceptDescription( new WikiPage( 'Nested' . $this->expansionProbe, SMW_NS_CONCEPT ) )
					);
				}

				return new FalseCondition();
			} );

		$instance->interpretDescription(
			new ConceptDescription( new WikiPage( 'Top', SMW_NS_CONCEPT ) )
		);

		$this->assertSame( $maxExpansions, $this->expansionProbe );
	}

	public function testInterpretDescriptionReleasesBudgetBetweenTopLevelConcepts() {
		// Budget of 1 with no nested expansion: each top-level concept must still
		// expand. If release() were not called, the first build would never return
		// the budget and the second concept would be wrongly rejected.
		$this->expansionProbe = 0;
		$conditionBuilder = $this->newCountingConditionBuilder();

		$conditionBuilder->method( 'mapDescriptionToCondition' )
			->willReturn( new FalseCondition() );

		$instance = new ConceptDescriptionInterpreter(
			$conditionBuilder,
			new ConceptExpansionGuard( 1 )
		);

		$instance->interpretDescription( new ConceptDescription( new WikiPage( 'First', SMW_NS_CONCEPT ) ) );
		$instance->interpretDescription( new ConceptDescription( new WikiPage( 'Second', SMW_NS_CONCEPT ) ) );

		$this->assertSame( 2, $this->expansionProbe );
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
