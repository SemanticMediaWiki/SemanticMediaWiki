<?php

namespace SMW\Tests\Elastic;

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
class ElasticFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $outputFormatter;
	private $conditionBuilder;
	private $connection;
	private $testEnvironment;

	protected function setUp() {

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
			->will( $this->returnValue( $options ) );

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
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
			$instance->newFileIndexer( $indexer )
		);
	}

	public function testCanConstructRollover() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rollover',
			$instance->newRollover( $this->connection )
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

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rebuilder',
			$instance->newRebuilder( $this->store )
		);
	}

	public function testCanConstructReplicationStatus() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Replication\ReplicationStatus',
			$instance->newReplicationStatus( $this->connection )
		);
	}

	public function testCanConstructCheckReplicationTask() {

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Replication\CheckReplicationTask',
			$instance->newCheckReplicationTask( $store )
		);
	}

	public function testCanConstructIndicatorProvider() {

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\IndicatorProvider',
			$instance->newIndicatorProvider( $store )
		);
	}

	public function testCanConstructInfoTaskHandler() {

		$store = $this->getMockBuilder( '\SMW\Elastic\ElasticStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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

	public function testOnTaskHandlerFactory() {

		$instance = new ElasticFactory();
		$taskHandlers = [];
		$outputFormatter = null;

		$this->assertTrue(
			$instance->onTaskHandlerFactory( $taskHandlers, $this->store, $outputFormatter, null )
		);
	}

	public function testOnApiTasks() {

		$instance = new ElasticFactory();
		$services = [];

		$this->assertTrue(
			$instance->onApiTasks( $services )
		);

		$this->assertArrayHasKey(
			'check-es-replication',
			$services
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

	public function testOnInvalidateEntityCache() {

		$instance = new ElasticFactory();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$dispatchContext = $this->getMockBuilder( '\Onoi\EventDispatcher\DispatchContext' )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext->expects( $this->once() )
			->method( 'get' )
			->will( $this->returnValue( $title ) );

		$instance->onInvalidateEntityCache( $dispatchContext );
	}

}
