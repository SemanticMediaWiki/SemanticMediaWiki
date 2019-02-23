<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\Rebuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Rebuilder
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
	private $indexer;
	private $propertyTableRowMapper;
	private $rollover;
	private $messageReporter;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableRowMapper = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowMapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->rollover = $this->getMockBuilder( '\SMW\Elastic\Indexer\Rollover' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\NullMessageReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Rebuilder::class,
			new Rebuilder( $this->connection, $this->indexer, $this->propertyTableRowMapper, $this->rollover )
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
			$this->propertyTableRowMapper,
			$this->rollover
		);

		$this->assertInternalType(
			'array',
			$instance->select( $store, [] )
		);
	}

	public function testDeleteAndSetupIndices() {

		$this->indexer->expects( $this->once() )
			->method( 'drop' );

		$this->indexer->expects( $this->once() )
			->method( 'setup' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->propertyTableRowMapper,
			$this->rollover
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
			$this->propertyTableRowMapper,
			$this->rollover
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
			$this->propertyTableRowMapper,
			$this->rollover
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
			$this->propertyTableRowMapper,
			$this->rollover
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
			$this->propertyTableRowMapper,
			$this->rollover
		);

		$instance->delete( 42 );
	}

	public function testRebuild() {

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->will( $this->returnValue( $changeDiff ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->propertyTableRowMapper->expects( $this->any() )
			->method( 'newChangeOp' )
			->will( $this->returnValue( $changeOp ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $options ) );

		$this->indexer->expects( $this->once() )
			->method( 'index' );

		$instance = new Rebuilder(
			$this->connection,
			$this->indexer,
			$this->propertyTableRowMapper,
			$this->rollover
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
			$this->propertyTableRowMapper,
			$this->rollover
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->refresh();
	}

}
