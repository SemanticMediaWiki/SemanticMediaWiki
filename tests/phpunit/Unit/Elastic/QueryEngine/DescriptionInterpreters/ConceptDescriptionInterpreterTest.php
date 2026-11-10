<?php

namespace SMW\Tests\Unit\Elastic\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Concept;
use SMW\DataItems\WikiPage;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\TermsLookup;
use SMW\Elastic\QueryEngine\TermsLookup\Parameters;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\Parser;
use SMW\Store;
use SMW\Utils\ConceptExpansionGuard;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends TestCase {

	private $conditionBuilder;
	private $descriptionFactory;
	private TermsLookup $termsLookup;
	private Store $store;
	private $queryParser;

	public function setUp(): void {
		$this->descriptionFactory = new DescriptionFactory();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMock();

		$parameters = $this->getMockBuilder( Parameters::class )
			->disableOriginalConstructor()
			->getMock();

		$this->termsLookup = $this->getMockBuilder( TermsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->termsLookup->expects( $this->any() )
			->method( 'newParameters' )
			->willReturn( $parameters );

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getTermsLookup', 'getStore', 'getID', 'interpretDescription' ] )
			->getMock();

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getTermsLookup' )
			->willReturn( $this->termsLookup );

		$this->queryParser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newInstance( int $maxExpansions = ConceptExpansionGuard::DEFAULT_MAX_EXPANSIONS ): ConceptDescriptionInterpreter {
		return new ConceptDescriptionInterpreter(
			$this->conditionBuilder,
			$this->queryParser,
			new ConceptExpansionGuard( $maxExpansions )
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptDescriptionInterpreter::class,
			$this->newInstance()
		);
	}

	public function testInterpretDescription_EmptyConcept() {
		$instance = $this->newInstance();

		$conceptDescription = $this->descriptionFactory->newConceptDescription(
			WikiPage::newFromText( 'Foo', SMW_NS_CONCEPT )
		);

		$this->assertEquals(
			[],
			$instance->interpretDescription( $conceptDescription )
		);
	}

	public function testInterpretDescription_AvailableConceptQuery() {
		$this->conditionBuilder->expects( $this->any() )
			->method( 'interpretDescription' )
			->willReturn( $this->conditionBuilder->newCondition( [ 'Foo' ] ) );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$concept = $this->getMockBuilder( Concept::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $concept ] );

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $description );

		$instance = $this->newInstance();

		$conceptDescription = $this->descriptionFactory->newConceptDescription(
			WikiPage::newFromText( 'Foo', SMW_NS_CONCEPT )
		);

		$this->assertEquals(
			'{"bool":{"must":["Foo"]}}',
			$instance->interpretDescription( $conceptDescription )
		);
	}

	public function testInterpretDescription_MutualConceptCycleIsBroken() {
		$conceptDescription = $this->descriptionFactory->newConceptDescription(
			WikiPage::newFromText( 'Foo', SMW_NS_CONCEPT )
		);

		$value = $this->getMockBuilder( Concept::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $value ] );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $description );

		$instance = $this->newInstance();

		// Re-enter the same concept once during expansion to simulate a cycle; the
		// nested call must short-circuit to [] instead of recursing without bound.
		$reentryResult = null;
		$reentered = false;

		$this->conditionBuilder->expects( $this->any() )
			->method( 'interpretDescription' )
			->willReturnCallback( function () use ( $instance, $conceptDescription, &$reentryResult, &$reentered ) {
				if ( !$reentered ) {
					$reentered = true;
					$reentryResult = $instance->interpretDescription( $conceptDescription );
				}

				return $this->conditionBuilder->newCondition( [ 'Foo' ] );
			} );

		$instance->interpretDescription( $conceptDescription );

		$this->assertSame( [], $reentryResult );
	}

	public function testInterpretDescription_FanOutExpansionIsBounded() {
		// A budget of 4, with a harness that would otherwise expand 10 distinct
		// (non-cyclic) concepts. The cycle guard never fires here, so only the
		// per-build expansion budget can stop the fan-out.
		$maxExpansions = 4;
		$harnessDepthLimit = 10;

		$expansions = 0;

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( function () use ( &$expansions ) {
				$expansions++;
				return [ $this->getMockBuilder( Concept::class )->disableOriginalConstructor()->getMock() ];
			} );

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $this->getMockBuilder( Description::class )->disableOriginalConstructor()->getMock() );

		$instance = $this->newInstance( $maxExpansions );

		// Each dispatch recurses into a fresh, distinct concept, up to the harness
		// limit, so nothing but the budget bounds the expansion.
		$this->conditionBuilder->expects( $this->any() )
			->method( 'interpretDescription' )
			->willReturnCallback( function () use ( $instance, &$expansions, $harnessDepthLimit ) {
				if ( $expansions < $harnessDepthLimit ) {
					$instance->interpretDescription(
						$this->descriptionFactory->newConceptDescription(
							WikiPage::newFromText( 'Nested' . $expansions, SMW_NS_CONCEPT )
						)
					);
				}

				return $this->conditionBuilder->newCondition( [ 'Foo' ] );
			} );

		$instance->interpretDescription(
			$this->descriptionFactory->newConceptDescription(
				WikiPage::newFromText( 'Top', SMW_NS_CONCEPT )
			)
		);

		$this->assertSame( $maxExpansions, $expansions );
	}

	public function testInterpretDescription_BudgetIsReleasedBetweenTopLevelConcepts() {
		// Budget of 1 with no nested expansion: each top-level concept must still
		// expand. If release() were not called, the first build would never return
		// the budget and the second concept would be wrongly rejected.
		$expansions = 0;

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( function () use ( &$expansions ) {
				$expansions++;
				return [ $this->getMockBuilder( Concept::class )->disableOriginalConstructor()->getMock() ];
			} );

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $this->getMockBuilder( Description::class )->disableOriginalConstructor()->getMock() );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'interpretDescription' )
			->willReturn( $this->conditionBuilder->newCondition( [ 'Foo' ] ) );

		$instance = $this->newInstance( 1 );

		$instance->interpretDescription(
			$this->descriptionFactory->newConceptDescription( WikiPage::newFromText( 'First', SMW_NS_CONCEPT ) )
		);
		$instance->interpretDescription(
			$this->descriptionFactory->newConceptDescription( WikiPage::newFromText( 'Second', SMW_NS_CONCEPT ) )
		);

		$this->assertSame( 2, $expansions );
	}

}
