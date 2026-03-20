<?php

namespace SMW\Tests\Elastic;

use MediaWiki\Title\Title;
use Onoi\EventDispatcher\DispatchContext;
use Onoi\EventDispatcher\Listener\GenericCallbackEventListener;
use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Hooks\UpdateEntityCollationComplete;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\Elastic\Indexer\Rebuilder\Rollover;
use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\Elastic\Indexer\Replication\ReplicationStatus;
use SMW\Elastic\Installer;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\QueryEngine;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\ElasticFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactoryTest extends TestCase {

	private MessageReporter $messageReporter;
	private $store;
	private $outputFormatter;
	private $conditionBuilder;
	private $connection;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();

		$options = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $options );

		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ElasticFactory::class,
			new ElasticFactory()
		);
	}

	public function testCanConstructConfig() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Config::class,
			$instance->newConfig()
		);
	}

	public function testCanConstructConnectionProvider() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ConnectionProvider::class,
			$instance->newConnectionProvider()
		);
	}

	public function testCanConstructIndexer() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Indexer::class,
			$instance->newIndexer( $this->store )
		);
	}

	public function testCanConstructFileIndexer() {
		$indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			FileIndexer::class,
			$instance->newFileIndexer( $this->store, $indexer )
		);
	}

	public function testCanConstructRollover() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Rollover::class,
			$instance->newRollover( $this->connection )
		);
	}

	public function testCanConstructInstaller() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Installer::class,
			$instance->newInstaller( $this->connection )
		);
	}

	public function testCanConstructBulk() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Bulk::class,
			$instance->newBulk( $this->connection )
		);
	}

	public function testCanConstructQueryEngine() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			QueryEngine::class,
			$instance->newQueryEngine( $this->store )
		);
	}

	public function testCanConstructRebuilder() {
		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			Rebuilder::class,
			$instance->newRebuilder( $store )
		);
	}

	public function testCanConstructUpdateEntityCollationComplete() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			UpdateEntityCollationComplete::class,
			$instance->newUpdateEntityCollationComplete( $this->store, $this->messageReporter )
		);
	}

	public function testCanConstructReplicationStatus() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ReplicationStatus::class,
			$instance->newReplicationStatus( $this->connection )
		);
	}

	public function testCanConstructReplicationCheck() {
		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ReplicationCheck::class,
			$instance->newReplicationCheck( $store )
		);
	}

	public function testCanConstructInfoTaskHandler() {
		$store = $this->getMockBuilder( ElasticStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ElasticClientTaskHandler::class,
			$instance->newInfoTaskHandler( $store, $this->outputFormatter )
		);
	}

	public function testCanConstructConceptDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ConceptDescriptionInterpreter::class,
			$instance->newConceptDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomePropertyInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			SomePropertyInterpreter::class,
			$instance->newSomePropertyInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomeValueInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			SomeValueInterpreter::class,
			$instance->newSomeValueInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructClassDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ClassDescriptionInterpreter::class,
			$instance->newClassDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructNamespaceDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			NamespaceDescriptionInterpreter::class,
			$instance->newNamespaceDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructValueDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ValueDescriptionInterpreter::class,
			$instance->newValueDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructConjunctionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			ConjunctionInterpreter::class,
			$instance->newConjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructDisjunctionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			DisjunctionInterpreter::class,
			$instance->newDisjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testOnEntityReferenceCleanUpComplete() {
		$instance = new ElasticFactory();

		$this->assertTrue(
			$instance->onEntityReferenceCleanUpComplete( $this->store, 42, null, false )
		);
	}

	public function testOnRegisterEventListeners() {
		$instance = new ElasticFactory();

		$eventListener = $this->getMockBuilder( GenericCallbackEventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			$instance->onRegisterEventListeners( $eventListener )
		);
	}

	public function testOnInvalidateEntityCache_OnSubject() {
		$instance = new ElasticFactory();

		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext = $this->getMockBuilder( DispatchContext::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext->expects( $this->once() )
			->method( 'has' )
			->with( 'subject' )
			->willReturn( $subject );

		$instance->onInvalidateEntityCache( $dispatchContext );
	}

	public function testOnInvalidateEntityCache_OnTitle() {
		$instance = new ElasticFactory();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$dispatchContext = $this->getMockBuilder( DispatchContext::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext->expects( $this->once() )
			->method( 'get' )
			->willReturn( $title );

		$instance->onInvalidateEntityCache( $dispatchContext );
	}

	public function testOnAfterUpdateEntityCollationComplete() {
		$updateEntityCollationComplete = $this->getMockBuilder( UpdateEntityCollationComplete::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = $this->getMockBuilder( ElasticFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newRebuilder', 'newUpdateEntityCollationComplete' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'newRebuilder' )
			->willReturn( $rebuilder );

		$instance->expects( $this->atLeastOnce() )
			->method( 'newUpdateEntityCollationComplete' )
			->willReturn( $updateEntityCollationComplete );

		$instance->onAfterUpdateEntityCollationComplete(
			$store,
			$this->messageReporter
		);
	}

	public function tesOnAfterUpdateEntityCollationComplete_SkipHook() {
		$connection = $this->getMockBuilder( DummyClient::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = $this->getMockBuilder( ElasticFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newRebuilder' ] )
			->getMock();

		$instance->expects( $this->never() )
			->method( 'newRebuilder' );

		$instance->onAfterUpdateEntityCollationComplete(
			$store,
			$this->messageReporter
		);
	}

}
