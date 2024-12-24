<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreterTest extends \PHPUnit\Framework\TestCase {

	private $querySegmentValidator;
	private $descriptionInterpreterFactory;
	private $queryParser;
	private $conditionBuilder;
	private $store;
	private $circularReferenceGuard;

	private $descriptionFactory;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$applicationFactory = ApplicationFactory::getInstance();
		$queryFactory = $applicationFactory->getQueryFactory();

		$this->descriptionFactory = $queryFactory->newDescriptionFactory();
		$this->queryParser = $queryFactory->newQueryParser();
		$this->dataItemFactory = $applicationFactory->getDataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->descriptionInterpreterFactory = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptDescriptionInterpreter::class,
			new ConceptDescriptionInterpreter( $this->store, $this->conditionBuilder, $this->circularReferenceGuard )
		);
	}

	public function testCheckForCircularReference() {
		$this->circularReferenceGuard->expects( $this->once() )
			->method( 'isCircular' )
			->with( 'concept-42' )
			->willReturn( true );

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIds );

		$instance = new ConceptDescriptionInterpreter(
			$this->store,
			$this->conditionBuilder,
			$this->circularReferenceGuard
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
			->onlyMethods( [ 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( $concept );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIds );

		$queryEngineFactory = new QueryEngineFactory(
			$this->store
		);

		$instance = new ConceptDescriptionInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder(),
			$this->circularReferenceGuard
		);

		$instance->setQueryParser(
			$this->queryParser
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
		$applicationFactory = ApplicationFactory::getInstance();

		$descriptionFactory = $applicationFactory->getQueryFactory()->newDescriptionFactory();
		$dataItemFactory = $applicationFactory->getDataItemFactory();

		# 0 No concept
		$concept = false;

		$description = $descriptionFactory->newConceptDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_CONCEPT )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = '';

		$provider[] = [
			$description,
			$concept,
			$expected
		];

		# 1 Cached concept
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

		$provider[] = [
			$description,
			$concept,
			$expected
		];

		# 2 Non cached concept
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
		$expected->components = [ 2 => 't1.o_id' ];
		$expected->queryNumber = 1;

		$provider[] = [
			$description,
			$concept,
			$expected
		];

		return $provider;
	}

}
