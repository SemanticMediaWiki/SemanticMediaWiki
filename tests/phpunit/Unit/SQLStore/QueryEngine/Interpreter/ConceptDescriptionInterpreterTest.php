<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\DataItemFactory;
use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;
	private $descriptionInterpreterFactory;

	private $descriptionFactory;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->descriptionFactory = new DescriptionFactory();
		$this->dataItemFactory = new DataItemFactory();

		$this->descriptionInterpreterFactory = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter',
			new ConceptDescriptionInterpreter( $querySegmentListBuilder )
		);
	}

	public function testCheckForCircularReference() {

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->once() )
			->method( 'isCircularByRecursionFor' )
			->with( $this->equalTo( 'concept-42' ) )
			->will( $this->returnValue( true ) );

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$querySegmentListBuilder->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$querySegmentListBuilder->expects( $this->any() )
			->method( 'getCircularReferenceGuard' )
			->will( $this->returnValue( $circularReferenceGuard ) );

		$instance = new ConceptDescriptionInterpreter(
			$querySegmentListBuilder
		);

		$description = $this->descriptionFactory->newConceptDescription(
			$this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_CONCEPT )
		);

		$instance->interpretDescription(
			$description
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testInterpretDescription( $description, $concept, $expected ) {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $concept ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$queryEngineFactory = new QueryEngineFactory( $store );

		$instance = new ConceptDescriptionInterpreter(
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
		$dataItemFactory = new DataItemFactory();

		#0 No concept
		$concept = false;

		$description = $descriptionFactory->newConceptDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_CONCEPT )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = '';

		$provider[] = array(
			$description,
			$concept,
			$expected
		);

		#1 Cached concept
		$concept = new \stdClass;
		$concept->concept_size = 1;
		$concept->concept_features = 1;
		$concept->concept_depth = 1;
		$concept->cache_date = strtotime( "now" );

		$description = $descriptionFactory->newConceptDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_CONCEPT )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = 't0.s_id';
		$expected->where = 't0.o_id=42';
		$expected->queryNumber = 0;

		$provider[] = array(
			$description,
			$concept,
			$expected
		);

		#2 Non cached concept
		$concept = new \stdClass;
		$concept->concept_txt = "[[Category:Foo]]";
		$concept->concept_size = 1;
		$concept->concept_features = 1;
		$concept->concept_depth = 1;
		$concept->cache_date = false;

		$description = $descriptionFactory->newConceptDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_CONCEPT )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = 't1.s_id';
		$expected->components = array( 2 => 't1.o_id' );
		$expected->queryNumber = 1;

		$provider[] = array(
			$description,
			$concept,
			$expected
		);

		return $provider;
	}

}
