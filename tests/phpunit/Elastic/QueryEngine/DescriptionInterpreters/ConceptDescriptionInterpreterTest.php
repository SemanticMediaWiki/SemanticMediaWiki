<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DIConcept;
use SMW\DIWikiPage;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\TermsLookup;
use SMW\Elastic\QueryEngine\TermsLookup\Parameters;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\Parser;
use SMW\Store;

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

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptDescriptionInterpreter::class,
			new ConceptDescriptionInterpreter( $this->conditionBuilder, $this->queryParser )
		);
	}

	public function testInterpretDescription_EmptyConcept() {
		$instance = new ConceptDescriptionInterpreter(
			$this->conditionBuilder,
			$this->queryParser
		);

		$conceptDescription = $this->descriptionFactory->newConceptDescription(
			DIWikiPage::newFromText( 'Foo', SMW_NS_CONCEPT )
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

		$concept = $this->getMockBuilder( DIConcept::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $concept ] );

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $description );

		$instance = new ConceptDescriptionInterpreter(
			$this->conditionBuilder,
			$this->queryParser
		);

		$conceptDescription = $this->descriptionFactory->newConceptDescription(
			DIWikiPage::newFromText( 'Foo', SMW_NS_CONCEPT )
		);

		$this->assertEquals(
			'{"bool":{"must":["Foo"]}}',
			$instance->interpretDescription( $conceptDescription )
		);
	}

}
