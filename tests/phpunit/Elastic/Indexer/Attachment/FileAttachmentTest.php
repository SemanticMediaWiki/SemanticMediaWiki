<?php

namespace SMW\Tests\Elastic\Indexer\Attachment;

use SMW\Elastic\Indexer\Attachment\FileAttachment;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\FileAttachment
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FileAttachmentTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $indexer;
	private $bulk;
	private $client;
	private $logger;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->bulk = $this->getMockBuilder( '\SMW\Elastic\Indexer\Bulk' )
			->disableOriginalConstructor()
			->getMock();

		$this->client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\NullLogger' )
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
		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$document = [
			'_source' => [
				'file_sha1' => '0123456789'
			]
		];

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ DIWikiPage::newFromText( 'Foo' ) ] );

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
		$dataItem = DIWikiPage::newFromText( __METHOD__ );
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
		$dataItem = DIWikiPage::newFromText( __METHOD__ );

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
		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->atLeastOnce() )
			->method( 'getCanonicalDiWikiPage' )
			->willReturn( DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) );

		$attachmentAnnotator = $this->getMockBuilder( '\SMW\Elastic\Indexer\Attachment\AttachmentAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$attachmentAnnotator->expects( $this->once() )
			->method( 'getProperty' )
			->willReturn( $property );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( 'Foo' ) );

		$semanticData->expects( $this->once() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ DIWikiPage::newFromText( 'Foobar' ) ] );

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
		$attachmentAnnotator = $this->getMockBuilder( '\SMW\Elastic\Indexer\Attachment\AttachmentAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( 'Foo' ) );

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
		$attachmentAnnotator = $this->getMockBuilder( '\SMW\Elastic\Indexer\Attachment\AttachmentAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( 'Foo' ) );

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
		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
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
