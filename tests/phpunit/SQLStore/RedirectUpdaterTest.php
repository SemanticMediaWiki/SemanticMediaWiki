<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SQLStore\RedirectUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\RedirectUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class RedirectUpdaterTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $tableFieldUpdater;
	private $propertyStatisticsStore;
	private $testEnvironment;
	private $idChanger;
	private $jobFactory;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->idChanger = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdChanger' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableFieldUpdater = $this->getMockBuilder( '\SMW\SQLStore\TableFieldUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
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
		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
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
		$cachingSemanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cachingSemanticDataLookup->expects( $this->any() )
			->method( 'invalidateCache' )
			 ->withConsecutive(
				[ $this->equalTo( 42 ) ],
				[ $this->equalTo( 0 ) ],
				[ $this->equalTo( 1001 ) ]
			 );

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
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
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$idTable->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->willReturn( 1 );

		$idTable->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->willReturn( 5 );

		$idTable->expects( $this->at( 1 ) )
			->method( 'findRedirect' )
			->willReturn( 0 );

		$idTable->expects( $this->never() )
			->method( 'deleteRedirect' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'query' )
			->willReturn( true );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
			->willReturn( new \SMW\Options() );

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
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$idTable->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->willReturn( 1 );

		$idTable->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->willReturn( 5 );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'query' )
			->willReturn( true );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
			->willReturn( new \SMW\Options() );

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
