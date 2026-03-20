<?php

namespace SMW\Tests\Elastic\Indexer\Attachment;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Attachment\AttachmentAnnotator;
use SMW\Elastic\Indexer\Attachment\FileAttachment;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\Indexer\Indexer;
use SMW\Store;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\FileAttachment
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FileAttachmentTest extends TestCase {

	private $store;
	private $indexer;
	private $bulk;
	private $client;
	private $logger;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->bulk = $this->getMockBuilder( Bulk::class )
			->disableOriginalConstructor()
			->getMock();

		$this->client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( NullLogger::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileAttachment::class,
			new FileAttachment( $this->store, $this->indexer, $this->bulk )
		);
	}

	public function testCreateAttachment() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$document = [
			'_source' => [
				'file_sha1' => '0123456789'
			]
		];

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromText( 'Foo' ) ] );

		$semanticData->expects( $this->once() )
			->method( 'addPropertyObjectValue' );

		$this->store->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->client->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->client->expects( $this->once() )
			->method( 'get' )
			->willReturn( $document );

		$this->bulk->expects( $this->once() )
			->method( 'upsert' );

		$this->indexer->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturn( 42 );

		$this->indexer->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $this->client );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$instance->setOrigin( __METHOD__ );
		$instance->setLogger( $this->logger );

		$instance->createAttachment( $dataItem );
	}

	public function testCreateAttachment_NoFileSha1() {
		$dataItem = WikiPage::newFromText( __METHOD__ );
		$document = [];

		$this->client->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->client->expects( $this->once() )
			->method( 'get' )
			->willReturn( $document );

		$this->indexer->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 42 );

		$this->indexer->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $this->client );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$instance->setLogger( $this->logger );

		$instance->createAttachment( $dataItem );
	}

	public function testCreateAttachment_NotExists() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$this->client->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );

		$this->client->expects( $this->never() )
			->method( 'get' );

		$this->indexer->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 42 );

		$this->indexer->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $this->client );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$instance->setLogger( $this->logger );

		$instance->createAttachment( $dataItem );
	}

	public function testIndexAttachmentInfo() {
		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->atLeastOnce() )
			->method( 'getCanonicalDiWikiPage' )
			->willReturn( WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) );

		$attachmentAnnotator = $this->getMockBuilder( AttachmentAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$attachmentAnnotator->expects( $this->once() )
			->method( 'getProperty' )
			->willReturn( $property );

		$semanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromText( 'Foo' ) );

		$semanticData->expects( $this->once() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromText( 'Foobar' ) ] );

		$attachmentAnnotator->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->bulk->expects( $this->once() )
			->method( 'clear' );

		$this->bulk->expects( $this->once() )
			->method( 'head' );

		$this->bulk->expects( $this->once() )
			->method( 'upsert' );

		$this->indexer->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturn( 42 );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$instance->setLogger( $this->logger );

		$instance->indexAttachmentInfo( $attachmentAnnotator );
	}

	public function testIndexAttachmentInfo_MissingId_ThrowsException() {
		$attachmentAnnotator = $this->getMockBuilder( AttachmentAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromText( 'Foo' ) );

		$attachmentAnnotator->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->indexer->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 0 );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$this->expectException( '\RuntimeException' );
		$instance->indexAttachmentInfo( $attachmentAnnotator );
	}

	public function testIndexAttachmentInfo_MissingIdOnConsecutiveCalls_ThrowsException() {
		$attachmentAnnotator = $this->getMockBuilder( AttachmentAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromText( 'Foo' ) );

		$attachmentAnnotator->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->indexer->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 0 );

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$this->expectException( '\RuntimeException' );
		$instance->indexAttachmentInfo( $attachmentAnnotator );
	}

	public function testCreateAttachment_MissingId_ThrowsException() {
		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FileAttachment(
			$this->store,
			$this->indexer,
			$this->bulk
		);

		$this->expectException( '\RuntimeException' );
		$instance->createAttachment( $dataItem );
	}

}
