<?php

namespace SMW\Tests\Unit\Elastic\Indexer\Rebuilder;

use Onoi\MessageReporter\NullMessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\Elastic\Installer;
use SMW\MediaWiki\Connection\Database;
use SMW\Store;

/**
 * @covers \SMW\Elastic\Indexer\Rebuilder\Rebuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RebuilderTest extends TestCase {

	private $connection;
	private $fileIndexer;
	private $indexer;
	private $documentCreator;
	private $installer;
	private $messageReporter;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileIndexer = $this->getMockBuilder( FileIndexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->documentCreator = $this->getMockBuilder( DocumentCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->installer = $this->getMockBuilder( Installer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( NullMessageReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'getVersion' )
			->willReturn( '6.4.0' );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Rebuilder::class,
			new Rebuilder( $this->connection, $this->indexer, $this->fileIndexer, $this->documentCreator, $this->installer )
		);
	}

	public function testSelect() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$this->assertIsArray(

			$instance->select( $store, [] )
		);
	}

	public function testDeleteAndSetupIndices() {
		$this->installer->expects( $this->once() )
			->method( 'drop' );

		$this->installer->expects( $this->once() )
			->method( 'setup' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->deleteAndSetupIndices();
	}

	public function testHasIndices() {
		$this->connection->expects( $this->once() )
			->method( 'hasIndex' )
			->willReturn( false );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$this->assertFalse(
			$instance->hasIndices()
		);
	}

	public function testCreateIndices() {
		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'createIndex' );
		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'updateAliases' );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'setLock' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->createIndices();
	}

	public function testSetDefaults() {
		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'openIndex' );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'closeIndex' );

		$this->connection->expects( $this->any() )
			->method( 'hasIndex' )
			->willReturn( true );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'releaseLock' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->setDefaults();
	}

	public function testDelete() {
		$this->connection->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->delete( 42 );
	}

	public function testRebuild() {
		$options = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$document = $this->getMockBuilder( Document::class )
			->disableOriginalConstructor()
			->getMock();

		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->documentCreator->expects( $this->any() )
			->method( 'newFromSemanticData' )
			->willReturn( $document );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $options );

		$this->indexer->expects( $this->once() )
			->method( 'indexDocument' )
			->with(
				$this->anything( $document ),
				false );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->rebuild( 42, $semanticData );
	}

	public function testRefresh() {
		$this->connection->expects( $this->any() )
			->method( 'hasIndex' )
			->willReturn( true );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'refresh' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->refresh();
	}

}
