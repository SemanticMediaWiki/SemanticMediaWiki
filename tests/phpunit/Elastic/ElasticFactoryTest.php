<?php

namespace SMW\Tests\Elastic;

use Onoi\MessageReporter\MessageReporter;
use SMW\Elastic\ElasticFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\ElasticFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactoryTest extends \PHPUnit\Framework\TestCase {

	private MessageReporter $messageReporter;
	private $store;
	private $outputFormatter;
	private $conditionBuilder;
	private $connection;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $options );

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
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
			'\SMW\Elastic\Config',
			$instance->newConfig()
		);
	}

	public function testCanConstructConnectionProvider() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Connection\ConnectionProvider',
			$instance->newConnectionProvider()
		);
	}

	public function testCanConstructIndexer() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Indexer',
			$instance->newIndexer( $this->store )
		);
	}

	public function testCanConstructFileIndexer() {
		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\FileIndexer',
			$instance->newFileIndexer( $this->store, $indexer )
		);
	}

	public function testCanConstructRollover() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rebuilder\Rollover',
			$instance->newRollover( $this->connection )
		);
	}

	public function testCanConstructInstaller() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Installer',
			$instance->newInstaller( $this->connection )
		);
	}

	public function testCanConstructBulk() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Bulk',
			$instance->newBulk( $this->connection )
		);
	}

	public function testCanConstructQueryEngine() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newQueryEngine( $this->store )
		);
	}

	public function testCanConstructRebuilder() {
		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rebuilder\Rebuilder',
			$instance->newRebuilder( $store )
		);
	}

	public function testCanConstructUpdateEntityCollationComplete() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Hooks\UpdateEntityCollationComplete',
			$instance->newUpdateEntityCollationComplete( $this->store, $this->messageReporter )
		);
	}

	public function testCanConstructReplicationStatus() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Replication\ReplicationStatus',
			$instance->newReplicationStatus( $this->connection )
		);
	}

	public function testCanConstructReplicationCheck() {
		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Replication\ReplicationCheck',
			$instance->newReplicationCheck( $store )
		);
	}

	public function testCanConstructInfoTaskHandler() {
		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Admin\ElasticClientTaskHandler',
			$instance->newInfoTaskHandler( $store, $this->outputFormatter )
		);
	}

	public function testCanConstructConceptDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter',
			$instance->newConceptDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomePropertyInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter',
			$instance->newSomePropertyInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomeValueInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter',
			$instance->newSomeValueInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructClassDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter',
			$instance->newClassDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructNamespaceDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter',
			$instance->newNamespaceDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructValueDescriptionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter',
			$instance->newValueDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructConjunctionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter',
			$instance->newConjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructDisjunctionInterpreter() {
		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter',
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

		$eventListener = $this->getMockBuilder( '\Onoi\EventDispatcher\Listener\GenericCallbackEventListener' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			$instance->onRegisterEventListeners( $eventListener )
		);
	}

	public function testOnInvalidateEntityCache_OnSubject() {
		$instance = new ElasticFactory();

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext = $this->getMockBuilder( '\Onoi\EventDispatcher\DispatchContext' )
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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$dispatchContext = $this->getMockBuilder( '\Onoi\EventDispatcher\DispatchContext' )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext->expects( $this->once() )
			->method( 'get' )
			->willReturn( $title );

		$instance->onInvalidateEntityCache( $dispatchContext );
	}

	public function testOnAfterUpdateEntityCollationComplete() {
		$updateEntityCollationComplete = $this->getMockBuilder( '\SMW\Elastic\Hooks\UpdateEntityCollationComplete' )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder = $this->getMockBuilder( '\SMW\Elastic\Indexer\Rebuilder\Rebuilder' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'newRebuilder', 'newUpdateEntityCollationComplete' ] )
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
		$connection = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'newRebuilder' ] )
			->getMock();

		$instance->expects( $this->never() )
			->method( 'newRebuilder' );

		$instance->onAfterUpdateEntityCollationComplete(
			$store,
			$this->messageReporter
		);
	}

}
