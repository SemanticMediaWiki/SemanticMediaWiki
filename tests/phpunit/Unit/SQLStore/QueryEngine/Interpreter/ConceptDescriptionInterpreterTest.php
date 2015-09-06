<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;
use SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Query\Language\ConceptDescription;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;

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

	protected function setUp() {
		parent::setUp();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter',
			new ConceptDescriptionInterpreter( $querySegmentListBuilder, $circularReferenceGuard )
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
			$querySegmentListBuilder,
			$circularReferenceGuard
		);

		$concept = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );

		$instance->interpretDescription(
			$concept
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $concept, $expected ) {

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

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptDescriptionInterpreter(
			new QuerySegmentListBuilder( $store ),
			$circularReferenceGuard
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

		#0 No concept
		$concept = false;
		$description = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );

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

		$description = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );

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

		$description = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );

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
