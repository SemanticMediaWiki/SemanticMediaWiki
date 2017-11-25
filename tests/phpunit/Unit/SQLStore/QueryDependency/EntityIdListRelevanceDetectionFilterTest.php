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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EntityIdListRelevanceDetectionFilter::class,
			new EntityIdListRelevanceDetectionFilter( $store, $changeOp )
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( array( 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ) )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( array( 29, 201, 202, 1001 ) ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setPropertyExemptionList(
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( array( 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ) )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( array( 1001 ) ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setAffiliatePropertyDetectionList(
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
			->setMethods( array( 'getDataItemById' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->with( $this->equalTo( 100 ) )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Has date', SMW_NS_PROPERTY ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( array( 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ) )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( array( 100, 201, 1001 ) ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setPropertyExemptionList(
			array( 'Has date' )
		);

		$this->assertEquals(
			array( 1001 ),
			$instance->getFilteredIdList()
		);
	}


}
