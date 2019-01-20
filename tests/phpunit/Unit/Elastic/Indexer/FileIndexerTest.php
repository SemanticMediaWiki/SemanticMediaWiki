<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\FileIndexer;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;

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

	private $indexer;
	private $logger;

	protected function setUp() {

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\NullLogger' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FileIndexer::class,
			new FileIndexer( $this->indexer )
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

		$this->indexer->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $client ) );

		$instance = new FileIndexer(
			$this->indexer
		);

		$instance->setReadCallback( function( $read_url ) use( $url ) {

			if ( $read_url !== $url ) {
				throw new \RuntimeException( "Invalid read URL!" );
			}

			return 'Foo';
		} );

		$instance->setLogger( $this->logger );

		$dataItem = DIWikiPage::newFromText( __METHOD__, NS_FILE );
		$dataItem->setId( 42 );

		$instance->index( $dataItem, $file );
	}

}
