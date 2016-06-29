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

	private $searchTable;
	private $connection;

	protected function setUp() {

		$this->searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater',
			new SearchTableUpdater( $this->searchTable, $this->connection )
		);
	}

	public function testRead() {

		$textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable->expects( $this->once() )
			->method( 'getTextSanitizer' )
			->will( $this->returnValue( $textSanitizer ) );

		$row = new \stdClass;
		$row->o_text = 'Foo';

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->equalTo( array( 'o_text' ) ),
				$this->equalTo( array( 's_id' => 12, 'p_id' => 42 ) ) )
			->will( $this->returnValue( $row ) );

		$instance = new SearchTableUpdater(
			$this->searchTable,
			$this->connection
		);

		$instance->read( 12, 42 );
	}

	public function testUpdateWithText() {

		$textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable->expects( $this->once() )
			->method( 'getTextSanitizer' )
			->will( $this->returnValue( $textSanitizer ) );

		$this->connection->expects( $this->once() )
			->method( 'update' );

		$instance = new SearchTableUpdater(
			$this->searchTable,
			$this->connection
		);

		$instance->update( 12, 42, 'foo' );
	}

	public function testDeleteOnUpdateWithEmptyText() {

		$this->connection->expects( $this->once() )
			->method( 'delete' );

		$this->connection->expects( $this->never() )
			->method( 'update' );

		$instance = new SearchTableUpdater(
			$this->searchTable,
			$this->connection
		);

		$instance->update( 12, 42, ' ' );
	}

	public function testInsert() {

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_id' => 12,
					'p_id' => 42,
					'o_text' => '' ) ) );

		$instance = new SearchTableUpdater(
			$this->searchTable,
			$this->connection
		);

		$instance->insert( 12, 42 );
	}

	public function testDelete() {

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_id' => 12,
					'p_id' => 42 ) ) );

		$instance = new SearchTableUpdater(
			$this->searchTable,
			$this->connection
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
			$this->searchTable,
			$this->connection
		);

		$instance->flushTable();
	}

}
