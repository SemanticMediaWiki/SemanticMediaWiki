<?php

namespace SMW\Tests\Unit\SQLStore\Rebuilder;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\Rebuilder\Rebuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class RebuilderTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $testEnvironment;
	private $titleFactory;
	private $entityValidator;
	private $propertyTableIdReferenceDisposer;
	private $jobFactory;
	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgAutoRefreshSubject' => true,
				'smwgMainCacheType' => 'hash',
				'smwgEnableUpdateJobs' => false,
			]
		);

		$this->titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityValidator = $this->getMockBuilder( EntityValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableIdReferenceDisposer = $this->getMockBuilder( PropertyTableIdReferenceDisposer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			Rebuilder::class,
			new Rebuilder( $store, $this->titleFactory, $this->entityValidator, $this->propertyTableIdReferenceDisposer, $this->jobFactory, $this->hookContainer )
		);
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testDispatchRebuildForSingleIteration( $id, $expected ) {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// matchAsTitle()'s newTitlesFromIDs() (call 0) and matchAsSubject()
		// (call 1) both need fetchResultSet() to iterate empty so emptyRange
		// stays true. The remaining callsites in next_position() and
		// getMaxId() use fetchField() and must return $expected.
		$callIndex = 0;
		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $expected, &$callIndex ) {
				$rows = ( $callIndex === 0 || $callIndex === 1 ) ? [] : [ [ $expected ] ];
				$callIndex++;
				return $this->createMockSelectQueryBuilder( $rows );
			} );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator,
			$this->propertyTableIdReferenceDisposer,
			$this->jobFactory,
			$this->hookContainer
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
		$this->entityValidator->expects( $this->any() )
			->method( 'hasLatestRevID' )
			->willReturn( true );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromRow' )
			->willReturn( $title );

		$row = [
			'smw_id' => 9999999999999999,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash' => [],
			'smw_rev' => 0
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// matchAsTitle()'s newTitlesFromIDs() (call 0) feeds a minimal page
		// row into fetchResultSet() so titleFactory->newFromRow() resolves to
		// $title. matchAsSubject() (call 1) feeds the smw_object_ids row.
		// Subsequent getMaxId() callsites use fetchField() and must return 500.
		$callIndex = 0;
		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$callIndex ) {
				if ( $callIndex === 0 ) {
					$rows = [ (object)[ 'page_id' => 1, 'page_namespace' => 0, 'page_title' => 'Foo' ] ];
				} elseif ( $callIndex === 1 ) {
					$rows = [ (object)$row ];
				} else {
					$rows = [ [ 500 ] ];
				}
				$callIndex++;
				return $this->createMockSelectQueryBuilder( $rows );
			} );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator,
			$this->propertyTableIdReferenceDisposer,
			$this->jobFactory,
			$this->hookContainer
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

	public function testUseJobEnqueuesViaBatchInsert() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->never() )
			->method( 'run' );

		$this->jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$this->jobFactory->expects( $this->once() )
			->method( 'batchInsert' )
			->with( [ $updateJob ] );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromRow' )
			->willReturn( $title );

		$this->entityValidator->expects( $this->any() )
			->method( 'inNamespace' )
			->willReturn( true );

		$this->entityValidator->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$row = [
			'smw_id' => 9999999999999999,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash' => [],
			'smw_rev' => 0
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// call 0: matchAsTitle()'s newTitlesFromIDs() page row -> title.
		// call 1: matchAsSubject()'s smw_object_ids row.
		// later calls: getMaxId()/next_position() fetchField() -> 500.
		$callIndex = 0;
		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$callIndex ) {
				if ( $callIndex === 0 ) {
					$rows = [ (object)[ 'page_id' => 1, 'page_namespace' => 0, 'page_title' => 'Foo' ] ];
				} elseif ( $callIndex === 1 ) {
					$rows = [ (object)$row ];
				} else {
					$rows = [ [ 500 ] ];
				}
				$callIndex++;
				return $this->createMockSelectQueryBuilder( $rows );
			} );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator,
			$this->propertyTableIdReferenceDisposer,
			$this->jobFactory,
			$this->hookContainer
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions(
			[
				'revision-mode' => false,
				'force-update' => false,
				'use-job' => true
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
