<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $connection;
	private $searchTable;
	private $textSanitizer;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater',
			new SearchTableUpdater( $this->connection, $this->searchTable, $this->textSanitizer )
		);
	}

	public function testRead() {

		$row = new \stdClass;
		$row->o_text = 'Foo';

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'o_text' ] ),
				$this->equalTo( [ 's_id' => 12, 'p_id' => 42 ] ) )
			->will( $this->returnValue( $row ) );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->read( 12, 42 );
	}

	public function testOptimizeOnEnabledType() {

		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->with( $this->equalTo( 'mysql' ) )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$this->assertTrue(
			$instance->optimize()
		);
	}

	public function testOptimizeOnDisabledType() {

		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->never() )
			->method( 'query' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$this->assertFalse(
			$instance->optimize()
		);
	}

	public function testUpdateWithText() {

		$this->connection->expects( $this->once() )
			->method( 'update' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->update( 12, 42, 'foo' );
	}

	public function testDeleteOnUpdateWithEmptyText() {

		$this->connection->expects( $this->once() )
			->method( 'delete' );

		$this->connection->expects( $this->never() )
			->method( 'update' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->update( 12, 42, ' ' );
	}

	public function testInsert() {

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( [
					's_id' => 12,
					'p_id' => 42,
					'o_text' => '' ] ) );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->insert( 12, 42 );
	}

	public function testDelete() {

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [
					's_id' => 12,
					'p_id' => 42 ] ) );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->delete( 12, 42 );
	}

	public function testFlushTable() {

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( '*' ) );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->flushTable();
	}

	public function testExists() {

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [
					's_id' => 12,
					'p_id' => 42 ] ) );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->exists( 12, 42 );
	}

}
