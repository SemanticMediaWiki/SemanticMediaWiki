<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableStatistics;

/**
 * @covers \SMW\SQLStore\TableStatistics
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.01
 *
 * @author mwjames
 */
class TableStatisticsTest extends \PHPUnit_Framework_TestCase {

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

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
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
			TableStatistics::class,
			new TableStatistics( $this->store )
		);
	}

	public function testGetStats() {

		$this->query->expects( $this->any() )
			->method( 'execute' )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)[ 'count' => 0 ] ) );

		$instance = new TableStatistics(
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

		$instance = new TableStatistics(
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

		$instance = new TableStatistics(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'rows_total_count' )
		);
	}

}
