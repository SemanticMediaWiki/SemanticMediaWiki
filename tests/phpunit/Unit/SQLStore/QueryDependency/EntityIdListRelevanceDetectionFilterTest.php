<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class EntityIdListRelevanceDetectionFilterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter',
			new EntityIdListRelevanceDetectionFilter( $store, $compositePropertyTableDiffIterator )
		);
	}

	public function testgetFilteredIdListOnExemptedPredefinedProperty() {

		$orderedDiffByTable = array(
			'fpt_mdat' => array(
				'property' => array(
					'key'  => '_MDAT',
					'p_id' => 29
				),
				'insert' => array(
					array(
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					)
				),
				'delete' => array(
					array(
						's_id' => 202,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					)
				)
			)
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->setMethods( array( 'getCombinedIdListOfChangedEntities', 'getOrderedDiffByTable' ) )
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getCombinedIdListOfChangedEntities' )
			->will( $this->returnValue( array( 29, 201, 202, 1001 ) ) );

		$compositePropertyTableDiffIterator->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$compositePropertyTableDiffIterator
		);

		$instance->setPropertyExemptionlist(
			array( '_MDAT' )
		);

		$this->assertEquals(
			array( 1001 ),
			$instance->getFilteredIdList()
		);
	}

	public function testgetFilteredIdListOnAffiliatePredefinedProperty() {

		$orderedDiffByTable = array(
			'fpt_dat' => array(
				'property' => array(
					'key'  => '_MDAT',
					'p_id' => 29
				),
				'insert' => array(
					array(
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					)
				),
				'delete' => array(
					array(
						's_id' => 202,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					)
				)
			)
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->setMethods( array( 'getCombinedIdListOfChangedEntities', 'getOrderedDiffByTable' ) )
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getCombinedIdListOfChangedEntities' )
			->will( $this->returnValue( array( 1001 ) ) );

		$compositePropertyTableDiffIterator->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$compositePropertyTableDiffIterator
		);

		$instance->setAffiliatePropertyDetectionlist(
			array( '_MDAT' )
		);

		$this->assertEquals(
			array( 1001, 201, 202 ),
			$instance->getFilteredIdList()
		);
	}

	public function testgetFilteredIdListOnExemptedUserdefinedProperty() {

		$orderedDiffByTable = array(
			'fpt_foo' => array(
				'insert' => array(
					array(
						'p_id' => 100,
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					)
				),
				'delete' => array(
					array(
						'p_id' => 100,
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					)
				)
			)
		);

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getDataItemForId' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemForId' )
			->with( $this->equalTo( 100 ) )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Has date', SMW_NS_PROPERTY ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->setMethods( array( 'getCombinedIdListOfChangedEntities', 'getOrderedDiffByTable' ) )
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getCombinedIdListOfChangedEntities' )
			->will( $this->returnValue( array( 100, 201, 1001 ) ) );

		$compositePropertyTableDiffIterator->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$compositePropertyTableDiffIterator
		);

		$instance->setPropertyExemptionlist(
			array( 'Has date' )
		);

		$this->assertEquals(
			array( 1001 ),
			$instance->getFilteredIdList()
		);
	}


}
