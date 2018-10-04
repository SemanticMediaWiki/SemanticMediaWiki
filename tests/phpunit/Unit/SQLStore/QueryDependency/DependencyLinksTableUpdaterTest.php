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
class DependencyLinksTableUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $spyLogger;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
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
			->setMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->will( $this->onConsecutiveCalls( 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( $insert ) );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

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
			->setMethods( [ 'getId', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( 0 ) );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$insert[] = [
			's_id' => 42,
			'o_id' => 1001
		];

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( $insert ) );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

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
