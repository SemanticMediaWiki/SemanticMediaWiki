<?php

namespace SMW\Tests\SQLStore;

use SMW\ApplicationFactory;
use SMW\SQLStore\EntityRebuildDispatcher;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\EntityRebuildDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EntityRebuildDispatcherTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $titleFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgSemanticsEnabled' => true,
				'smwgAutoRefreshSubject' => true,
				'smwgCacheType' => 'hash',
				'smwgEnableUpdateJobs' => false,
			]
		);

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( 0 ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityRebuildDispatcher',
			new EntityRebuildDispatcher( $store, $this->titleFactory )
		);
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testDispatchRebuildForSingleIteration( $id, $expected ) {

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromIDs' )
			->will( $this->returnValue( [] ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $expected ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new EntityRebuildDispatcher(
			$store,
			$this->titleFactory
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions(
			[
				'shallow-update' => true,
				'use-job' => false
			]
		);

		$instance->rebuild( $id );

		$this->assertSame(
			$expected,
			$id
		);

		$this->assertLessThanOrEqual(
			1,
			$instance->getEstimatedProgress()
		);
	}

	public function testRevisionMode() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromIDs' )
			->will( $this->returnValue( [ $title ] ) );

		$row = [
			'smw_id' => 9999999999999999,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash' => [],
			'smw_rev' => 0
		];

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( [ (object)$row] ) );

		$connection->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( 500 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new EntityRebuildDispatcher(
			$store,
			$this->titleFactory
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions(
			[
				'revision-mode' => true,
				'force-update' => false,
				'use-job' => false
			]
		);

		$id = 999999999;

		$instance->rebuild( $id );
	}

	public function idProvider() {

		$provider[] = [
			42, // Within the border Id
			43
		];

		$provider[] = [
			9999999999999999999,
			-1
		];

		return $provider;
	}
}
