<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use SMW\SQLStore\EntityStore\FieldList;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use SMW\Utils\HmacSerializer;

/**
 * @covers \SMW\SQLStore\EntityStore\AuxiliaryFields
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AuxiliaryFieldsTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $connection;
	private $idCacheManager;
	private InMemoryLruCache $cache;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new InMemoryLruCache();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AuxiliaryFields::class,
			new AuxiliaryFields( $this->connection, $this->idCacheManager )
		);
	}

	public function testPrefetchFieldList() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$subjects = [ WikiPage::newFromText( 'Foo' ) ];

		$row = [
			'smw_id' => 42,
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ),
			'smw_countmap' => 0
		];

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertInstanceOf(
			FieldList::class,
			$instance->prefetchFieldList( $subjects )
		);

		$this->assertContains(
			[ 't.smw_hash' => [ sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ) ] ],
			$whereConditions
		);
	}

	public function testSetFieldMaps_Empty() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$tables = $rows = $sets = $uniqueIndexFields = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [], [] );

		$this->assertSame( [ 'smw_object_aux' ], $tables );
		$this->assertSame(
			[ [ 'smw_id' => 42, 'smw_seqmap' => null, 'smw_countmap' => null ] ],
			$rows
		);
		$this->assertSame( [ [ 'smw_id' ] ], $uniqueIndexFields );
		$this->assertSame(
			[ [ 'smw_seqmap' => null, 'smw_countmap' => null ] ],
			$sets
		);
	}

	public function testSetFieldMaps() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'escape_bytea' )
			->willReturnArgument( 0 );

		$tables = $rows = $sets = $uniqueIndexFields = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [ 'seqmap' ], [ 'countmap' ] );

		$this->assertSame( [ 'smw_object_aux' ], $tables );
		$this->assertSame(
			[ [
				'smw_id' => 42,
				'smw_seqmap' => HmacSerializer::compress( [ 'seqmap' ] ),
				'smw_countmap' => HmacSerializer::compress( [ 'countmap' ] )
			] ],
			$rows
		);
		$this->assertSame( [ [ 'smw_id' ] ], $uniqueIndexFields );
		$this->assertSame(
			[ [
				'smw_seqmap' => HmacSerializer::compress( [ 'seqmap' ] ),
				'smw_countmap' => HmacSerializer::compress( [ 'countmap' ] )
			] ],
			$sets
		);
	}

}
