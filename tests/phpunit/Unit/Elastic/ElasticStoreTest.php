<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\ElasticStore;
use SMW\Options;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\ElasticStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticStoreTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $elasticFactory;
	private $spyMessageReporter;

	protected function setUp() {

		$this->elasticFactory = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ElasticStore::class,
			new ElasticStore()
		);
	}

	public function testSetup() {

		$row = new \stdClass;
		$row->smw_id = \SMW\SQLStore\SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;
		$row->smw_proptable_hash = 'foo';
		$row->smw_hash = 42;
		$row->smw_rev = null;
		$row->smw_touched = null;
		$row->count = 0;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$database->expects( $this->any() )
			->method( 'query' )
			->will( $this->returnValue( [] ) );

		$database->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [ $row ] ) );

		$database->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function( $type ) use( $connection, $database ) {

			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnCallback( $callback ) );

		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$indexer->expects( $this->once() )
			->method( 'setup' )
			->will( $this->returnValue( [] ) );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newIndexer' )
			->will( $this->returnValue( $indexer ) );

		$instance = new ElasticStore();
		$instance->setConnectionManager( $connectionManager );
		$instance->setElasticFactory( $this->elasticFactory );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$options = new Options(
			[
				'verbose' => true
			]
		);

		$instance->setup( $options );

		$this->assertContains(
			'Setting up indices',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testDrop() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$database->expects( $this->any() )
			->method( 'listTables' )
			->will( $this->returnValue( [] ) );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function( $type ) use( $connection, $database ) {

			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $database;
		};

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnCallback( $callback ) );

		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$indexer->expects( $this->once() )
			->method( 'drop' )
			->will( $this->returnValue( [] ) );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newIndexer' )
			->will( $this->returnValue( $indexer ) );

		$instance = new ElasticStore();
		$instance->setConnectionManager( $connectionManager );
		$instance->setElasticFactory( $this->elasticFactory );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->drop( true );

		$this->assertContains(
			'Dropping indices',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
