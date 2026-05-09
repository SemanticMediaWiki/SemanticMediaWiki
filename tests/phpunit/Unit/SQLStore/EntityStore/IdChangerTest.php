<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\EntityStore\IdChanger
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class IdChangerTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $testEnvironment;
	private $store;
	private $connection;
	private $jobFactory;
	private $updateJob;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IdChanger::class,
			new IdChanger( $this->store )
		);
	}

	public function testMove_NoMatch() {
		$selectWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [], $selectWheres );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new IdChanger(
			$this->store
		);

		$instance->move( 42 );

		$this->assertSame(
			[ [ 'smw_id' => 42 ] ],
			$selectWheres
		);
	}

	public function testMove_ZeroTarget() {
		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'FOO',
			'smw_sort' => 'FOO',
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true )
		];

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$this->connection->expects( $this->once() )
			->method( 'nextSequenceValue' )
			->willReturn( 1 );

		$insertTables = $insertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $insertTables, $insertRows );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->willReturn( 9999 );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$this->connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$updateBuilder = $this->createMockUpdateQueryBuilder();

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [] );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $this->updateJob );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = [ 'smw_id' => 9999 ] + $row;

		$this->assertEquals(
			(object)$expected,
			$instance->move( 42, 0 )
		);

		$this->assertSame(
			[ [ 'smw_id' => 1 ] + $row ],
			$insertRows
		);
		$this->assertSame(
			[ SQLStore::ID_AUXILIARY_TABLE, SQLStore::ID_TABLE ],
			$deleteTables
		);
		$this->assertSame(
			[ [ 'smw_id' => 42 ], [ 'smw_id' => 42 ] ],
			$deleteWheres
		);
	}

	public function testMove_Target() {
		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'FOO',
			'smw_sort' => 'FOO',
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true )
		];

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$insertTables = $insertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $insertTables, $insertRows );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$this->connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$updateBuilder = $this->createMockUpdateQueryBuilder();

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [] );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $this->updateJob );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = [ 'smw_id' => 1001 ] + $row;

		$this->assertEquals(
			(object)$expected,
			$instance->move( 42, 1001 )
		);

		$this->assertSame(
			[ [ 'smw_id' => 1001 ] + $row ],
			$insertRows
		);
		$this->assertSame(
			[ SQLStore::ID_AUXILIARY_TABLE, SQLStore::ID_TABLE ],
			$deleteTables
		);
		$this->assertSame(
			[ [ 'smw_id' => 42 ], [ 'smw_id' => 42 ] ],
			$deleteWheres
		);
	}

	public function testChange_IdSubject_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$table->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$table->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->willReturn( [ '_foo' => FieldType::FIELD_ID ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [ $table ] );

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)[] ] );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$updateTables = $updateSets = $updateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder( $updateTables, $updateSets, $updateWheres );

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001 );

		$this->assertContains( [ 's_id' => 1001 ], $updateSets );
		$this->assertContains( [ 'p_id' => 1001 ], $updateSets );
		$this->assertContains( [ '_foo' => 1001 ], $updateSets );
	}

	public function testChange_IdSubject_PropertyNS_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$table->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$table->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->willReturn( [ '_foo' => FieldType::FIELD_ID ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [ $table ] );

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)[] ] );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$updateBuilder = $this->createMockUpdateQueryBuilder();

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001, SMW_NS_PROPERTY, NS_MAIN );

		$this->assertSame(
			[ [ 'p_id' => 42 ] ],
			$deleteWheres
		);
	}

	public function testChange_IdSubject_ConceptNS_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$table->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$table->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [ $table ] );

		$selectWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)[] ], $selectWheres );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$updateBuilder = $this->createMockUpdateQueryBuilder();

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001, SMW_NS_CONCEPT, NS_MAIN );

		$this->assertSame(
			[ [ 's_id' => 42 ] ],
			$selectWheres
		);
		$this->assertSame(
			[ [ 's_id' => 42 ], [ 's_id' => 42 ] ],
			$deleteWheres
		);
	}

}
