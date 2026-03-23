<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DependencyLinksTableUpdaterTest extends TestCase {

	private $testEnvironment;
	private $spyLogger;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DependencyLinksTableUpdater::class,
			new DependencyLinksTableUpdater( $this->store )
		);
	}

	public function testAddToUpdateList() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 1001 );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				SQLStore::QUERY_LINKS_TABLE,
				[ 's_id' => 42 ] );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				SQLStore::QUERY_LINKS_TABLE,
				$insert );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DependencyLinksTableUpdater(
			$store
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->clear();

		$instance->addToUpdateList( 42, [ WikiPage::newFromText( 'Bar' ) ] );
		$instance->doUpdate();
	}

	public function testAddToUpdateListOnNull_List() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 42, null )
		);
	}

	public function testAddToUpdateListOnZero_Id() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 0, [] )
		);
	}

	public function testAddToUpdateListOnEmpty_List() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 42, [] )
		);
	}

	public function testAddDependenciesFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getId', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturn( 0 );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				SQLStore::QUERY_LINKS_TABLE,
				[ 's_id' => 42 ] );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				SQLStore::QUERY_LINKS_TABLE,
				$insert );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DependencyLinksTableUpdater(
			$store
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->clear();

		$instance->addToUpdateList( 42, [ WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) ] );
		$instance->doUpdate();
	}

}
