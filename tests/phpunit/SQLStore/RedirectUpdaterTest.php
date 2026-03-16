<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DIWikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\NullJob;
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
		$nullJob = $this->getMockBuilder( NullJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $nullJob );

		$instance = new RedirectUpdater(
			$this->store,
			$this->idChanger,
			$this->tableFieldUpdater,
			$this->propertyStatisticsStore
		);

		$instance->triggerChangeTitleUpdate(
			DIWikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )->getTitle(),
			DIWikiPage::newFromText( __METHOD__ . '-new', NS_MAIN )->getTitle(),
			[ 'redirect_id' => 0 ]
		);
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
			DIWikiPage::newFromText( __METHOD__ . '-old', NS_MAIN )
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

		$connection->expects( $this->once() )
			->method( 'query' )
			->willReturn( true );

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
			DIWikiPage::newFromText( __METHOD__ . '-old', NS_MAIN ),
			DIWikiPage::newFromText( __METHOD__ . '-new', NS_MAIN ),
			[ 'page_id' => 9999, 'redirect_id' => 0 ]
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

		$database->expects( $this->once() )
			->method( 'query' )
			->willReturn( true );

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
			DIWikiPage::newFromText( __METHOD__ . '-old', NS_MAIN ),
			DIWikiPage::newFromText( __METHOD__ . '-new', NS_MAIN ),
			[ 'page_id' => 9999, 'redirect_id' => 1111 ]
		);
	}

}
