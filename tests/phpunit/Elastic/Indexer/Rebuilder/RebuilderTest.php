<?php

namespace SMW\Tests\Elastic\Indexer\Rebuilder;

use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Rebuilder\Rebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RebuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;
	private $fileIndexer;
	private $indexer;
	private $documentCreator;
	private $installer;
	private $messageReporter;

	protected function setUp() : void {

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileIndexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\FileIndexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->documentCreator = $this->getMockBuilder( '\SMW\Elastic\Indexer\DocumentCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->installer = $this->getMockBuilder( '\SMW\Elastic\Installer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\NullMessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Rebuilder::class,
			new Rebuilder( $this->connection, $this->indexer, $this->fileIndexer, $this->documentCreator, $this->installer )
		);
	}

	public function testSelect() {

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->fileIndexer,
			$this->documentCreator,
			$this->installer
		);

		$this->assertInternalType(
			'array',
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
			->will( $this->returnValue( false ) );

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

		$indices = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'delete', 'existsAlias', 'updateAliases' ] )
			->getMock();

		$indices->expects( $this->atLeastOnce() )
			->method( 'updateAliases' );

		$this->connection->expects( $this->any() )
			->method( 'indices' )
			->will( $this->returnValue( $indices ) );

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

		$indices = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'delete', 'close', 'open' ] )
			->getMock();

		$indices->expects( $this->atLeastOnce() )
			->method( 'open' );

		$indices->expects( $this->atLeastOnce() )
			->method( 'close' );

		$this->connection->expects( $this->any() )
			->method( 'indices' )
			->will( $this->returnValue( $indices ) );

		$this->connection->expects( $this->any() )
			->method( 'hasIndex' )
			->will( $this->returnValue( true ) );

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

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->documentCreator->expects( $this->any() )
			->method( 'newFromSemanticData' )
			->will( $this->returnValue( $document ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $options ) );

		$this->indexer->expects( $this->once() )
			->method( 'indexDocument' )
			->with(
				$this->anything( $document ),
				$this->equalTo( false ) );

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
			->will( $this->returnValue( true ) );

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
