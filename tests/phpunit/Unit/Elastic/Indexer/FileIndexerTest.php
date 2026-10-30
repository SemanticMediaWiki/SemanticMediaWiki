<?php

namespace SMW\Tests\Unit\Elastic\Indexer;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Attachment\FileAttachment;
use SMW\Elastic\Indexer\Attachment\FileHandler;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\MediaWiki\RevisionGuard;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

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

	private $indexer;
	private $fileHandler;
	private $fileAttachment;
	private $logger;
	private BagOStuff $cache;
	private $revisionGuard;
	private Store $store;

	protected function setUp(): void {
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

		$this->cache = new HashBagOStuff();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileIndexer::class,
			new FileIndexer( $this->store, $this->cache, $this->fileHandler, $this->fileAttachment )
		);
	}

	public function testIndex() {
		$dataItem = $this->indexFile();

		$this->assertSame(
			[ 'result' => 'created' ],
			$this->cache->get( FileIndexer::makeIngestResponseKey( $this->cache, $dataItem->getTitle() ) )
		);
	}

	public function testIngestResponseExpires() {
		$now = 1700000000.0;
		$this->cache->setMockTime( $now );
		$dataItem = $this->indexFile();

		$now += BagOStuff::TTL_MONTH + 1;

		$this->assertFalse(
			$this->cache->get( FileIndexer::makeIngestResponseKey( $this->cache, $dataItem->getTitle() ) )
		);
	}

	public function testIngestResponseIsKeptPerFile() {
		$this->assertNotSame(
			FileIndexer::makeIngestResponseKey( $this->cache, Title::makeTitle( NS_FILE, 'Foo.pdf' ) ),
			FileIndexer::makeIngestResponseKey( $this->cache, Title::makeTitle( NS_FILE, 'Bar.pdf' ) )
		);
	}

	private function indexFile(): WikiPage {
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

		$client->expects( $this->any() )
			->method( 'index' )
			->willReturn( [ 'result' => 'created' ] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $client );

		$instance = new FileIndexer(
			$this->store,
			$this->cache,
			$this->fileHandler,
			$this->fileAttachment
		);

		$instance->setLogger( $this->logger );
		$instance->setRevisionGuard( $this->revisionGuard );

		$dataItem = WikiPage::newFromText( __METHOD__, NS_FILE );
		$dataItem->setId( 42 );

		$instance->index( $dataItem, $file );

		return $dataItem;
	}

}
