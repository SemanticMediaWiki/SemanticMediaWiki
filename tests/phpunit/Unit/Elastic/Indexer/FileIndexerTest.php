<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\FileIndexer;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\FileIndexer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileIndexerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $indexer;
	private $fileHandler;
	private $fileAttachment;
	private $logger;
	private $entityCache;
	private $revisionGuard;

	protected function setUp() : void {

		$this->testEnvironment =  new TestEnvironment();

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileHandler = $this->getMockBuilder( '\SMW\Elastic\Indexer\Attachment\FileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileAttachment = $this->getMockBuilder( '\SMW\Elastic\Indexer\Attachment\FileAttachment' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\NullLogger' )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->setMethods( [ 'save', 'associate', 'fetch' ] )
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() : void {
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
			->will( $this->returnValue( $url ) );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getFile' )
			->will( $this->returnValue( $file ) );

		$ingest = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'putPipeline' ] )
			->getMock();

		$ingest->expects( $this->once() )
			->method( 'putPipeline' );

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->once() )
			->method( 'ingest' )
			->will( $this->returnValue( $ingest ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $client ) );

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
