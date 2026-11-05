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

		// matchAsSubject() (call 0) and matchAsTitle()'s newTitlesFromIDs()
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

		// matchAsSubject() (call 0) feeds the smw_object_ids row.
		// matchAsTitle()'s newTitlesFromIDs() (call 1) feeds a minimal page
		// row into fetchResultSet() so titleFactory->newFromRow() resolves to
		// $title. Subsequent getMaxId() callsites use fetchField() and must return 500.
		$callIndex = 0;
		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$callIndex ) {
				if ( $callIndex === 0 ) {
					$rows = [ (object)$row ];
				} elseif ( $callIndex === 1 ) {
					$rows = [ (object)[ 'page_id' => 1, 'page_namespace' => 0, 'page_title' => 'Foo' ] ];
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

		// call 0: matchAsSubject()'s smw_object_ids row.
		// call 1: matchAsTitle()'s newTitlesFromIDs() page row -> title.
		// later calls: getMaxId()/next_position() fetchField() -> 500.
		$callIndex = 0;
		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$callIndex ) {
				if ( $callIndex === 0 ) {
					$rows = [ (object)$row ];
				} elseif ( $callIndex === 1 ) {
					$rows = [ (object)[ 'page_id' => 1, 'page_namespace' => 0, 'page_title' => 'Foo' ] ];
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

	public function testRedirectRowIsCleanedUpWhenItsPageIsDispatchedInTheSameStep(): void {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'exists' )->willReturn( true );

		$this->titleFactory->method( 'newFromRow' )->willReturn( $title );
		$this->titleFactory->method( 'makeTitleSafe' )->willReturn( $title );
		$this->entityValidator->method( 'inNamespace' )->willReturn( true );
		$this->entityValidator->method( 'isSemanticEnabled' )->willReturn( true );
		$this->entityValidator->method( 'isRedirect' )->willReturn( true );
		$this->jobFactory->method( 'newUpdateJob' )->willReturn( $this->createMock( UpdateJob::class ) );

		$instance = $this->newRebuilderAnswering( [
			[ (object)[
				'smw_id' => 600,
				'smw_title' => 'Foo',
				'smw_namespace' => NS_MAIN,
				'smw_iw' => SMW_SQL3_SMWREDIIW,
				'smw_subobject' => '',
				'smw_sortkey' => 'Foo',
				'smw_proptable_hash' => null,
				'smw_rev' => null
			] ],
			[ (object)[ 'page_id' => 600, 'page_namespace' => NS_MAIN, 'page_title' => 'Foo' ] ]
		] );

		$this->propertyTableIdReferenceDisposer->expects( $this->once() )
			->method( 'cleanUpTableEntriesById' )
			->with( 600 );

		$id = 600;

		$instance->rebuild( $id );
	}

	/**
	 * @dataProvider nextIdProvider
	 */
	public function testEmptyStepContinuesAtTheNearerOfTheNextPageAndEntityId( int $nextPageId, int $nextEntityId, int $expected ): void {
		// Entity and title selects find nothing; then the next page ID and the next entity ID
		$instance = $this->newRebuilderAnswering( [ [], [], [ [ $nextPageId ] ], [ [ $nextEntityId ] ] ] );
		$id = 1001;

		$instance->rebuild( $id );

		$this->assertSame( $expected, $id );
	}

	public static function nextIdProvider(): iterable {
		yield 'page ID nearer' => [ 1002, 1005, 1002 ];
		yield 'entity ID nearer' => [ 1005, 1002, 1002 ];
		yield 'no further page' => [ 0, 1005, 1005 ];
		yield 'no further entity' => [ 1002, 0, 1002 ];
	}

	/**
	 * Rebuilds one ID per step without jobs
	 *
	 * @param array[] $results Rows of each select, in order; later selects find the highest ID 2000
	 */
	private function newRebuilderAnswering( array $results ): Rebuilder {
		$connection = $this->createMock( Database::class );
		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$results ) {
				return $this->createMockSelectQueryBuilder( array_shift( $results ) ?? [ [ 2000 ] ] );
			} );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->method( 'getConnection' )->willReturn( $connection );

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator,
			$this->propertyTableIdReferenceDisposer,
			$this->jobFactory,
			$this->hookContainer
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions( [ 'use-job' => false ] );

		return $instance;
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
