<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Options;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectUpdater;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\RedirectUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class RedirectUpdaterTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private $tableFieldUpdater;
	private $propertyStatisticsStore;
	private $testEnvironment;
	private $idChanger;
	private $jobFactory;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idChanger = $this->getMockBuilder( IdChanger::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableFieldUpdater = $this->getMockBuilder( TableFieldUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RedirectUpdater::class,
			new RedirectUpdater( $this->store, $this->idChanger, $this->tableFieldUpdater, $this->propertyStatisticsStore )
		);
	}

	public function testTriggerChangeTitleUpdate() {
		// CLI (PHPUnit) context: the job runs synchronously, never queued.
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->once() )
			->method( 'run' );

		$updateJob->expects( $this->never() )
			->method( 'lazyPush' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$instance = new RedirectUpdater(
			$this->store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		);

		$instance->triggerChangeTitleUpdate(
			WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )->getTitle(),
			WikiPage::newFromText( __METHOD__ . '-new', NS_MAIN )->getTitle(),
			[ 'redirect_id' => 0 ]
		);
	}

	public function testTriggerChangeTitleUpdatePushesJobToQueueWhenOnline() {
		$source = WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )->getTitle();
		$target = WikiPage::newFromText( __METHOD__ . '-new', NS_MAIN )->getTitle();

		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->once() )
			->method( 'lazyPush' );

		$updateJob->expects( $this->never() )
			->method( 'run' );

		$pushed = [];

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturnCallback( static function ( $title, $parameters ) use ( &$pushed, $updateJob ) {
				$pushed[$title->getPrefixedText()] = $parameters;
				return $updateJob;
			} );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->with( 'smwgEnableUpdateJobs' )
			->willReturn( true );

		$instance = $this->newOnlineRedirectUpdater();

		// redirect_id == 0 -> only the target is refreshed.
		$instance->triggerChangeTitleUpdate( $source, $target, [ 'redirect_id' => 0 ] );

		$this->assertSame( [ $target->getPrefixedText() ], array_keys( $pushed ) );
		$this->assertSame(
			[ UpdateJob::FORCED_UPDATE => true, 'origin' => 'ChangeTitleUpdate' ],
			$pushed[$target->getPrefixedText()]
		);
	}

	public function testTriggerChangeTitleUpdatePushesBothTitlesToQueueWhenRedirectIdSet() {
		$source = WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )->getTitle();
		$target = WikiPage::newFromText( __METHOD__ . '-new', NS_MAIN )->getTitle();

		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		// redirect_id != 0 -> both the old title and the target are refreshed.
		$updateJob->expects( $this->exactly( 2 ) )
			->method( 'lazyPush' );

		$updateJob->expects( $this->never() )
			->method( 'run' );

		$pushed = [];

		$this->jobFactory->expects( $this->exactly( 2 ) )
			->method( 'newUpdateJob' )
			->willReturnCallback( static function ( $title, $parameters ) use ( &$pushed, $updateJob ) {
				$pushed[$title->getPrefixedText()] = $parameters;
				return $updateJob;
			} );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->with( 'smwgEnableUpdateJobs' )
			->willReturn( true );

		$instance = $this->newOnlineRedirectUpdater();

		$instance->triggerChangeTitleUpdate( $source, $target, [ 'redirect_id' => 42 ] );

		// Both the source and the target are queued (not the same title twice),
		// each with the static parameters that the queue dedup relies on (no
		// per-edit data that would defeat collapsing repeated same-target jobs).
		$this->assertEqualsCanonicalizing(
			[ $source->getPrefixedText(), $target->getPrefixedText() ],
			array_keys( $pushed )
		);

		$expectedParameters = [
			UpdateJob::FORCED_UPDATE => true,
			'origin' => 'ChangeTitleUpdate',
		];

		$this->assertSame( $expectedParameters, $pushed[$source->getPrefixedText()] );
		$this->assertSame( $expectedParameters, $pushed[$target->getPrefixedText()] );
	}

	/**
	 * A RedirectUpdater that reports a non-CLI (web request) context, so the
	 * queue branch of triggerChangeTitleUpdate is exercised. PHPUnit otherwise
	 * always runs as CLI.
	 */
	private function newOnlineRedirectUpdater(): RedirectUpdater {
		return new class(
			$this->store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		) extends RedirectUpdater {
			protected function isCommandLineMode(): bool {
				return false;
			}
		};
	}

	public function testInvalidateLookupCache() {
		$cachingSemanticDataLookup = $this->getMockBuilder( CachingSemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$cachingSemanticDataLookup->expects( $this->any() )
			->method( 'invalidateCache' )
			 ->withConsecutive(
				[ $this->equalTo( 42 ) ],
				[ $this->equalTo( 0 ) ],
				[ $this->equalTo( 1001 ) ]
			 );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageIDandSort' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'findRedirect' )
			->willReturn( 1001 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new RedirectUpdater(
			$this->store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		);

		$instance->setEqualitySupport( SMW_EQ_NONE );

		$instance->updateRedirects(
			WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )
		);

		$instance->invalidateLookupCache(
			$cachingSemanticDataLookup
		);
	}

	public function testChangeTitleForMainNamespaceWithoutRedirectId() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$idTable->expects( $this->exactly( 2 ) )
			->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( 1, 5 );

		$idTable->expects( $this->once() )
			->method( 'findRedirect' )
			->willReturn( 0 );

		$idTable->expects( $this->never() )
			->method( 'deleteRedirect' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new Options() );

		$instance = new RedirectUpdater(
			$store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		);

		$instance->doUpdate(
			WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN ),
			WikiPage::newFromText( __METHOD__ . '-new', NS_MAIN ),
			[ 'page_id' => 9999, 'redirect_id' => 0 ]
		);

		$this->assertSame(
			[ SQLStore::ID_TABLE ],
			$capturedTables
		);
	}

	public function testChangeTitleForMainNamespaceWithRedirectId() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$idTable->expects( $this->exactly( 2 ) )
			->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( 1, 5 );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$database->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new Options() );

		$instance = new RedirectUpdater(
			$store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		);

		$instance->doUpdate(
			WikiPage::newFromText( __METHOD__ . '-old', NS_MAIN ),
			WikiPage::newFromText( __METHOD__ . '-new', NS_MAIN ),
			[ 'page_id' => 9999, 'redirect_id' => 1111 ]
		);

		$this->assertSame(
			[ SQLStore::ID_TABLE ],
			$capturedTables
		);
	}

}
