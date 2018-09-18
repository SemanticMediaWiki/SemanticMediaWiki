<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter;
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
	private $spyLogger;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

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

		$orderedDiffByTable = [
			'fpt_mdat' => [
				'property' => [
					'key'  => '_MDAT',
					'p_id' => 29
				],
				'insert' => [
					[
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					]
				],
				'delete' => [
					[
						's_id' => 202,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					]
				]
			]
		];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( [ 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ] )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [ 29, 201, 202, 1001 ] ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setPropertyExemptionList(
			[ '_MDAT' ]
		);

		$this->assertEquals(
			[ 1001 ],
			$instance->getFilteredIdList()
		);
	}

	public function testgetFilteredIdListOnAffiliatePredefinedProperty() {

		$orderedDiffByTable = [
			'fpt_dat' => [
				'property' => [
					'key'  => '_MDAT',
					'p_id' => 29
				],
				'insert' => [
					[
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					]
				],
				'delete' => [
					[
						's_id' => 202,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					]
				]
			]
		];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( [ 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ] )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [ 1001 ] ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setAffiliatePropertyDetectionList(
			[ '_MDAT' ]
		);

		$this->assertEquals(
			[ 1001, 201, 202 ],
			$instance->getFilteredIdList()
		);
	}

	public function testgetFilteredIdListOnExemptedUserdefinedProperty() {

		$orderedDiffByTable = [
			'fpt_foo' => [
				'insert' => [
					[
						'p_id' => 100,
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/48/0',
						'o_sortkey' => '2457540.9595833'
					]
				],
				'delete' => [
					[
						'p_id' => 100,
						's_id' => 201,
						'o_serialized' => '1/2016/6/1/11/1/59/0',
						'o_sortkey' => '2457540.9582292'
					]
				]
			]
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->with( $this->equalTo( 100 ) )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Has date', SMW_NS_PROPERTY ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->setMethods( [ 'getChangedEntityIdSummaryList', 'getOrderedDiffByTable' ] )
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [ 100, 201, 1001 ] ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( $orderedDiffByTable ) );

		$instance = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setPropertyExemptionList(
			[ 'Has date' ]
		);

		$this->assertEquals(
			[ 1001 ],
			$instance->getFilteredIdList()
		);
	}


}
