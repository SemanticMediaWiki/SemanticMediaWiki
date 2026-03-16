<?php

namespace SMW\Tests\Elastic\Indexer;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DIWikiPage;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Attachment\FileAttachment;
use SMW\Elastic\Indexer\Attachment\FileHandler;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\EntityCache;
use SMW\MediaWiki\RevisionGuard;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\FileIndexer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FileIndexerTest extends TestCase {

	private $testEnvironment;
	private $indexer;
	private $fileHandler;
	private $fileAttachment;
	private $logger;
	private $entityCache;
	private $revisionGuard;
	private Store $store;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileHandler = $this->getMockBuilder( FileHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileAttachment = $this->getMockBuilder( FileAttachment::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( NullLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->setMethods( [ 'save', 'associate', 'fetch' ] )
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileIndexer::class,
			new FileIndexer( $this->store, $this->entityCache, $this->fileHandler, $this->fileAttachment )
		);
	}

	public function testIndex() {
		$url = 'http://example.org/Foo.txt';

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'getFullURL' )
			->willReturn( $url );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getFile' )
			->willReturn( $file );

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->once() )
			->method( 'ingestPutPipeline' );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $client );

		$this->entityCache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:entity:d2711ab614dfb2d68dcea212c71769d5' ) );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' )
			->with(
				$this->anything(),
				$this->stringContains( 'smw:entity:d2711ab614dfb2d68dcea212c71769d5' ) );

		$instance = new FileIndexer(
			$this->store,
			$this->entityCache,
			$this->fileHandler,
			$this->fileAttachment
		);

		$instance->setLogger( $this->logger );
		$instance->setRevisionGuard( $this->revisionGuard );

		$dataItem = DIWikiPage::newFromText( __METHOD__, NS_FILE );
		$dataItem->setId( 42 );

		$instance->index( $dataItem, $file );
	}

}
