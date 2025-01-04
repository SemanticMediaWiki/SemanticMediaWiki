<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\ElasticStore;
use SMW\Elastic\Config;
use SMW\Options;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Elastic\ElasticStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticStoreTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $elasticFactory;
	private $setupFile;
	private $spyMessageReporter;
	private $spyLogger;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'SetupFile', $this->setupFile );

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
		$this->spyLogger = $utilityFactory->newSpyLogger();
	}

	public function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ElasticStore::class,
			new ElasticStore()
		);
	}

	public function testSetup() {
		$row = new \stdClass;
		$row->smw_id = \SMW\SQLStore\SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;
		$row->smw_proptable_hash = 'foo';
		$row->smw_hash = 42;
		$row->smw_rev = null;
		$row->smw_touched = null;
		$row->count = 0;

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );
		$database->expects( $this->any() )
			->method( 'getServerInfo' )
			->willReturn( '5.7.21' );

		$database->expects( $this->any() )
			->method( 'query' )
			->willReturn( [] );

		$database->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$database->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( $row );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $connection, $database, $client ) {
			if ( $type === 'elastic' ) {
				return $client;
			};

			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$installer = $this->getMockBuilder( '\SMW\Elastic\Installer' )
			->disableOriginalConstructor()
			->getMock();

		$installer->expects( $this->once() )
			->method( 'newSetupFile' )
			->willReturn( $this->setupFile );

		$installer->expects( $this->once() )
			->method( 'setup' )
			->willReturn( [] );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newInstaller' )
			->willReturn( $installer );

		$instance = new ElasticStore();
		$instance->setConnectionManager( $connectionManager );
		$instance->setElasticFactory( $this->elasticFactory );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$options = new Options(
			[
				'verbose' => true
			]
		);

		$instance->setup( $options );

		$this->assertContains(
			'Indices setup',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testDrop() {
		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$database->expects( $this->any() )
			->method( 'listTables' )
			->willReturn( [] );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $connection, $database, $client ) {
			if ( $type === 'elastic' ) {
				return $client;
			};

			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$installer = $this->getMockBuilder( '\SMW\Elastic\Installer' )
			->disableOriginalConstructor()
			->getMock();

		$installer->expects( $this->once() )
			->method( 'drop' )
			->willReturn( [] );

		$installer->expects( $this->once() )
			->method( 'newSetupFile' )
			->willReturn( $this->setupFile );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newInstaller' )
			->willReturn( $installer );

		$instance = new ElasticStore();
		$instance->setConnectionManager( $connectionManager );
		$instance->setElasticFactory( $this->elasticFactory );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->drop( true );

		$this->assertContains(
			'Indices removal',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateData_PushFileIngestJob() {
		$config = new Config(
			[
				'indexer.experimental.file.ingest' => true
			]
		);

		$subject = DIWikiPage::newFromText( __METHOD__, NS_FILE );

		// Check that the IngestJob is referencing to the same subject instance
		$checkJobParameterCallback = function ( $job ) use( $subject ) {
			return DIWikiPage::newFromTitle( $job->getTitle() )->equals( $subject );
		};

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( $checkJobParameterCallback ) );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSubject', 'getPropertyValues', 'getProperties', 'getSubSemanticData' ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$semanticData->setOption( 'is_fileupload', true );

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $connection, $client ) {
			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $client;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$documentCreator = $this->getMockBuilder( '\SMW\Elastic\Indexer\DocumentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$documentCreator->expects( $this->once() )
			->method( 'newFromSemanticData' )
			->willReturn( $document );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newIndexer' )
			->willReturn( $indexer );

		$this->elasticFactory->expects( $this->any() )
			->method( 'newDocumentCreator' )
			->willReturn( $documentCreator );

		$instance = new ElasticStore();

		$instance->setConnectionManager( $connectionManager );
		$instance->setElasticFactory( $this->elasticFactory );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setLogger( $this->spyLogger );

		$instance->updateData( $semanticData );
	}

}
