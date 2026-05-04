<?php

namespace SMW\Tests\Unit\Elastic;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Installer;
use SMW\MediaWiki\Connection\Database;
use SMW\Options;
use SMW\SetupFile;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\Elastic\ElasticStore
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticStoreTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $testEnvironment;
	private $elasticFactory;
	private $setupFile;
	private $spyMessageReporter;
	private $spyLogger;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory = $this->getMockBuilder( ElasticFactory::class )
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
		$row = new stdClass;
		$row->smw_id = SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;
		$row->smw_proptable_hash = 'foo';
		$row->smw_hash = 42;
		$row->smw_rev = null;
		$row->smw_touched = null;
		$row->smw_title = '';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject = '';
		$row->count = 0;

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );

		$connection->expects( $this->any() )
			->method( 'newInsertQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockInsertQueryBuilder() );

		$connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockUpdateQueryBuilder() );

		$connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockDeleteQueryBuilder() );

		$connection->expects( $this->any() )
			->method( 'newReplaceQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockReplaceQueryBuilder() );

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
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$database->expects( $this->any() )
			->method( 'query' )
			->willReturn( [] );

		$database->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );

		$database->expects( $this->any() )
			->method( 'newInsertQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockInsertQueryBuilder() );

		$database->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockUpdateQueryBuilder() );

		$database->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockDeleteQueryBuilder() );

		$database->expects( $this->any() )
			->method( 'newReplaceQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockReplaceQueryBuilder() );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$callback = static function ( $type ) use( $connection, $database, $client ) {
			if ( $type === 'elastic' ) {
				return $client;
			}

			if ( $type === 'mw.db' ) {
				return $connection;
			}

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$installer = $this->getMockBuilder( Installer::class )
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

		$this->assertStringContainsString(
			'Indices setup',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testDrop() {
		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$database->expects( $this->any() )
			->method( 'listTables' )
			->willReturn( [] );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$callback = static function ( $type ) use( $connection, $database, $client ) {
			if ( $type === 'elastic' ) {
				return $client;
			}

			if ( $type === 'mw.db' ) {
				return $connection;
			}

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$installer = $this->getMockBuilder( Installer::class )
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

		$this->assertStringContainsString(
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

		$subject = WikiPage::newFromText( __METHOD__, NS_FILE );

		// Check that the IngestJob is referencing to the same subject instance
		$checkJobParameterCallback = static function ( $job ) use( $subject ) {
			return WikiPage::newFromTitle( $job->getTitle() )->equals( $subject );
		};

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( $checkJobParameterCallback ) );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->setMethods( [ 'getSubject', 'getPropertyValues', 'getProperties', 'getSubSemanticData' ] )
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

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$qb = $this->createMockSelectQueryBuilder( [] );
		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$insertBuilder = $this->createMockInsertQueryBuilder();
		$connection->expects( $this->any() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$callback = static function ( $type ) use( $connection, $client ) {
			if ( $type === 'mw.db' ) {
				return $connection;
			}

			return $client;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$document = $this->getMockBuilder( Document::class )
			->disableOriginalConstructor()
			->getMock();

		$documentCreator = $this->getMockBuilder( DocumentCreator::class )
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
