<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;
use SMW\Tests\TestEnvironmentTrait;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $conditionBuilder;
	private $descriptionFactory;
	private $queryParser;

	public function setUp() {

		$this->descriptionFactory = new DescriptionFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMock();

		$parameters = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\TermsLookup\Parameters' )
			->disableOriginalConstructor()
			->getMock();

		$this->termsLookup = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\TermsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->termsLookup->expects( $this->any() )
			->method( 'newParameters' )
			->will( $this->returnValue( $parameters ) );

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'getTermsLookup', 'getStore', 'getID', 'interpretDescription' ] )
			->getMock();

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getTermsLookup' )
			->will( $this->returnValue( $this->termsLookup ) );

		$this->queryParser = $this->getMockBuilder( '\SMW\Query\Parser' )
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
			->will( $this->returnValue( $this->conditionBuilder->newCondition( [ 'Foo' ] ) ) );

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$concept = $this->getMockBuilder( '\SMWDIConcept' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $concept ] ) );

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->will( $this->returnValue( $description ) );

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
