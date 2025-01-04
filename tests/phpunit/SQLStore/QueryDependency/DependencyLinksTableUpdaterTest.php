<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DependencyLinksTableUpdaterTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $spyLogger;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
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
			'\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater',
			new DependencyLinksTableUpdater( $this->store )
		);
	}

	public function testAddToUpdateList() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 1001 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				[ 's_id' => 42 ] );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				$insert );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
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

		$instance->addToUpdateList( 42, [ DIWikiPage::newFromText( 'Bar' ) ] );
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
			->onlyMethods( [ 'getId', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturn( 0 );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				[ 's_id' => 42 ] );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				$insert );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
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

		$instance->addToUpdateList( 42, [ DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) ] );
		$instance->doUpdate();
	}

}
