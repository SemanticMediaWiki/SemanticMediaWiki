<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\TextIndexer;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\TextIndexer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TextIndexerTest extends \PHPUnit_Framework_TestCase {

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
			TextIndexer::class,
			new TextIndexer( $this->indexer )
		);
	}

	public function testIndex() {

		$bulk = $this->getMockBuilder( '\SMW\Elastic\Indexer\Bulk' )
			->disableOriginalConstructor()
			->getMock();

		$bulk->expects( $this->once() )
			->method( 'upsert' );

		$this->indexer->expects( $this->once() )
			->method( 'newBulk' )
			->will( $this->returnValue( $bulk ) );

		$instance = new TextIndexer(
			$this->indexer
		);

		$instance->setLogger( $this->logger );

		$dataItem = DIWikiPage::newFromText( __METHOD__ );
		$dataItem->setId( 42 );

		$instance->index( $dataItem, '' );
	}

	public function testIndex_MissingIDThrowsException() {

		$instance = new TextIndexer(
			$this->indexer
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->index( DIWikiPage::newFromText( __METHOD__ ), '' );
	}


}
