<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Util\Validator\QueryContainerValidator;

use SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\ConceptDescription;

use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptDescriptionCompilerTest extends \PHPUnit_Framework_TestCase {

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = new QueryContainerValidator();
	}

	public function testCanConstruct() {

		$queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler',
			new ConceptDescriptionCompiler( $queryBuilder )
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
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$instance = new ConceptDescriptionCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
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
