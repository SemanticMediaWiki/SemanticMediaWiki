<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\TableStatisticsLookup;

/**
 * @covers \SMW\SQLStore\Lookup\TableStatisticsLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.01
 *
 * @author mwjames
 */
class TableStatisticsLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $query;

	protected function setUp() {

		$this->query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableStatisticsLookup::class,
			new TableStatisticsLookup( $this->store )
		);
	}

	public function testGetStats() {

		$this->query->expects( $this->any() )
			->method( 'execute' )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)[ 'count' => 0 ] ) );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertInternalType(
			'array',
			$instance->getStats()
		);
	}

	public function testGet_last_id() {

		$this->connection->expects( $this->any() )
			->method( 'selectField' )
			->with(
				$this->anything(),
				$this->stringContains( 'MAX(smw_id)' ) )
			->will( $this->returnValue( "42" ) );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'last_id' )
		);
	}

	public function testGet_rows_total_count() {

		$this->connection->expects( $this->any() )
			->method( 'selectField' )
			->with(
				$this->anything(),
				$this->stringContains( 'Count(*)' ) )
			->will( $this->returnValue( "42" ) );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'rows_total_count' )
		);
	}

}
