<?php

namespace SMW\Tests\Unit\Elastic\Indexer\Rebuilder;

use ArrayObject;
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
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

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

		$whereConditions = [];
		$capturedSelects = [];
		$database->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( &$whereConditions, &$capturedSelects ) {
					return $this->createMockSelectQueryBuilder( [], $whereConditions, $capturedSelects );
				}
			);

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

		$this->assertIsArray( $instance->select( $store, [] ) );

		$this->assertSame(
			[ [ 'smw_id', 'smw_iw', 'smw_rev' ], 'MAX(smw_id)' ],
			$capturedSelects
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

	public function testDeletionIsSentInTheBatchAfterTheDocumentsBeforeIt() {
		$batches = $this->recordBatches();
		$instance = $this->newRebuilderWithConfig( [] );

		$instance->rebuild( 10, new SemanticData( WikiPage::newFromText( 'Referencing page' ) ) );
		$instance->delete( 50 );
		$instance->flush();

		$this->assertSame(
			[ [ [ 10, Document::TYPE_INSERT ], [ 50, Document::TYPE_DELETE ] ] ],
			$batches->getArrayCopy()
		);
	}

	public function testDocumentsAreSentInBatchesOfAHundredPages() {
		$batches = $this->recordBatches();
		$instance = $this->newRebuilderWithConfig( [] );

		for ( $id = 1; $id <= 250; $id++ ) {
			$instance->rebuild( $id, new SemanticData( WikiPage::newFromText( "Page $id" ) ) );
		}

		$instance->flush();

		$this->assertSame( [ 100, 100, 50 ], array_map( 'count', $batches->getArrayCopy() ) );
	}

	public function testBatchIsSentBeforeItWouldExceedTenMegabytes() {
		$batches = $this->recordBatches();
		$instance = $this->newRebuilder( [], static fn ( SemanticData $semanticData ) => new Document(
			$semanticData->getSubject()->getId(),
			[ 'text' => str_repeat( 'x', 4 * 1024 * 1024 ) ]
		) );

		for ( $id = 1; $id <= 4; $id++ ) {
			$instance->rebuild( $id, new SemanticData( WikiPage::newFromText( "Page $id" ) ) );
		}

		$instance->flush();

		$this->assertSame( [ 2, 2 ], array_map( 'count', $batches->getArrayCopy() ) );
	}

	public function testDocumentsGoToTheIndexVersionsCreatedForTheRebuild() {
		$this->connection->method( 'getLock' )->willReturn( false );
		$this->connection->method( 'createIndex' )->willReturn( 'v2' );
		$this->connection->method( 'hasIndex' )->willReturn( true );
		$this->connection->method( 'indexExists' )->willReturn( true );

		$currentVersions = [];
		$versionsWhenSent = [];
		$this->indexer->method( 'setVersions' )
			->willReturnCallback( static function ( array $versions ) use ( &$currentVersions ) {
				$currentVersions = $versions;
			} );
		$this->indexer->method( 'indexDocuments' )
			->willReturnCallback( static function () use ( &$currentVersions, &$versionsWhenSent ) {
				$versionsWhenSent[] = $currentVersions;
			} );

		$instance = $this->newRebuilderWithConfig( [] );
		$instance->createIndices();
		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo' ) ) );
		$instance->flush();

		$this->assertSame( [ [ Client::TYPE_DATA => 'v2', Client::TYPE_LOOKUP => 'v2' ] ], $versionsWhenSent );
	}

	public function testPagesOutsideTheFileNamespaceStayBatchedWithFileIngestEnabled() {
		$batches = $this->recordBatches();
		$instance = $this->newRebuilderWithConfig( [ 'indexer.experimental.file.ingest' => true ] );

		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo' ) ) );
		$instance->rebuild( 2, new SemanticData( WikiPage::newFromText( 'Bar' ) ) );
		$instance->flush();

		$this->assertSame( [ 2 ], array_map( 'count', $batches->getArrayCopy() ) );
	}

	public function testHasPendingDocumentsUntilTheyAreSent() {
		$instance = $this->newRebuilderWithConfig( [] );
		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo' ) ) );
		$pendingBeforeFlush = $instance->hasPendingDocuments();

		$instance->flush();

		$this->assertSame( [ true, false ], [ $pendingBeforeFlush, $instance->hasPendingDocuments() ] );
	}

	public function testPageDocumentIsSentBeforeItsFileIsIndexed() {
		$calls = [];
		$this->indexer->method( 'indexDocuments' )
			->willReturnCallback( static function () use ( &$calls ) {
				$calls[] = 'page document';
			} );
		$this->fileIndexer->method( 'index' )
			->willReturnCallback( static function () use ( &$calls ) {
				$calls[] = 'file';
			} );

		$instance = $this->newRebuilderWithConfig( [ 'indexer.experimental.file.ingest' => true ] );
		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo.pdf', NS_FILE ) ) );

		$this->assertSame( [ 'page document', 'file' ], $calls );
	}

	public function testFileIsNotIndexedWithFileIngestDisabled() {
		$this->fileIndexer->expects( $this->never() )->method( 'index' );

		$instance = $this->newRebuilderWithConfig( [] );
		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo.pdf', NS_FILE ) ) );
	}

	public function testFileIsNotIndexedWhenFileIndexingIsSkipped() {
		$this->fileIndexer->expects( $this->never() )->method( 'index' );

		$instance = $this->newRebuilderWithConfig( [ 'indexer.experimental.file.ingest' => true ] );
		$instance->set( 'skip-fileindex', true );
		$instance->rebuild( 1, new SemanticData( WikiPage::newFromText( 'Foo.pdf', NS_FILE ) ) );
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

	/**
	 * @return ArrayObject Each batch sent to the indexer, as a list of [ document ID, document type ]
	 */
	private function recordBatches(): ArrayObject {
		$batches = new ArrayObject();

		$this->indexer->method( 'indexDocuments' )
			->willReturnCallback( static function ( array $documents ) use ( $batches ) {
				$batches[] = array_map(
					static fn ( Document $document ) => [
						$document->getId(),
						$document->isType( Document::TYPE_DELETE ) ? Document::TYPE_DELETE : Document::TYPE_INSERT
					],
					$documents
				);
			} );

		return $batches;
	}

	/**
	 * @param array<string,mixed> $settings Config values by dotted key; others are false
	 */
	private function newRebuilderWithConfig( array $settings ): Rebuilder {
		return $this->newRebuilder(
			$settings,
			static fn ( SemanticData $semanticData ) => new Document( $semanticData->getSubject()->getId() )
		);
	}

	/**
	 * @param array<string,mixed> $settings Config values by dotted key; others are false
	 * @param callable $newDocument Creates the document of a page's SemanticData
	 */
	private function newRebuilder( array $settings, callable $newDocument ): Rebuilder {
		$config = $this->createMock( Config::class );
		$config->method( 'dotGet' )
			->willReturnCallback( static fn ( string $key ) => $settings[$key] ?? false );

		$this->connection->method( 'getConfig' )
			->willReturn( $config );

		$this->documentCreator->method( 'newFromSemanticData' )
			->willReturnCallback( $newDocument );

		return new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);
	}

}
