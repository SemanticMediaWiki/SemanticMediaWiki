<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\TableFieldUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableFieldUpdaterTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TableFieldUpdater::class,
			new TableFieldUpdater( $store )
		);
	}

	public function testUpdateSortField() {
		$collator = $this->getMockBuilder( Collator::class )
			->disableOriginalConstructor()
			->getMock();

		$collator->expects( $this->once() )
			->method( 'getSortKey' )
			->willReturn( 'Foo' );

		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )->willReturn( '1970' );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store,
			$collator
		);

		$instance->updateSortField( 42, 'Foo' );

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedTables );

		$this->assertSame(
			[ [ 'smw_sortkey' => 'Foo', 'smw_sort' => 'Foo', 'smw_touched' => '1970' ] ],
			$capturedSets
		);

		$this->assertSame(
			[ [ 'smw_id' => 42 ] ],
			$capturedWheres
		);
	}

	public function testUpdateRevField() {
		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )->willReturn( '1970' );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateRevField( 42, 1001 );

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedTables );

		$this->assertSame(
			[ [ 'smw_rev' => 1001, 'smw_touched' => '1970' ] ],
			$capturedSets
		);

		$this->assertSame(
			[ [ 'smw_id' => 42 ] ],
			$capturedWheres
		);
	}

	public function testUpdateTouchedField() {
		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )->willReturn( '1970' );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateTouchedField( 42 );

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedTables );

		$this->assertSame(
			[ [ 'smw_touched' => '1970' ] ],
			$capturedSets
		);

		$this->assertSame(
			[ [ 'smw_id' => 42 ] ],
			$capturedWheres
		);
	}

	public function testUpdateIwField() {
		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateIwField( 42, 'foo', 'abc1234' );

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedTables );

		$this->assertSame(
			[ [ 'smw_iw' => 'foo', 'smw_hash' => 'abc1234' ] ],
			$capturedSets
		);

		$this->assertSame(
			[ [ 'smw_id' => 42 ] ],
			$capturedWheres
		);
	}

}
