<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\IdChanger;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\EntityStore\IdChanger
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class IdChangerTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $store;
	private $connection;
	private $jobFactory;
	private $nullJob;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				'*',
				[ 'smw_id' => 42 ] )
			->willReturn( false );

		$instance = new IdChanger(
			$this->store
		);

		$instance->move( 42 );
	}

	public function testMove_ZeroTarget() {
		if ( !method_exists( '\PHPUnit\Framework\MockObject\Builder\InvocationMocker', 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit\Framework\MockObject\Builder\InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'FOO',
			'smw_sort' => 'FOO',
			'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6'
		];

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				'*',
				[ 'smw_id' => 42 ] )
			->willReturn( (object)$row );

		$this->connection->expects( $this->once() )
			->method( 'nextSequenceValue' )
			->willReturn( '__seq__' );

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				[ 'smw_id' => '__seq__' ] + $row );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->willReturn( 9999 );

		$this->connection->expects( $this->any() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( 'smw_object_aux' ), $this->equalTo( [ 'smw_id' => 42 ] ) ],
				[ $this->equalTo( 'smw_object_ids' ), $this->equalTo( [ 'smw_id' => 42 ] ) ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [] );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $this->nullJob );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = [ 'smw_id' => 9999 ] + $row;

		$this->assertEquals(
			(object)$expected,
			$instance->move( 42, 0 )
		);
	}

	public function testMove_Target() {
		if ( !method_exists( '\PHPUnit\Framework\MockObject\Builder\InvocationMocker', 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit\Framework\MockObject\Builder\InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'FOO',
			'smw_sort' => 'FOO',
			'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6'
		];

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				'*',
				[ 'smw_id' => 42 ] )
			->willReturn( (object)$row );

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				[ 'smw_id' => 1001 ] + $row );

		$this->connection->expects( $this->any() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( 'smw_object_aux' ), $this->equalTo( [ 'smw_id' => 42 ] ) ],
				[ $this->equalTo( 'smw_object_ids' ), $this->equalTo( [ 'smw_id' => 42 ] ) ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [] );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $this->nullJob );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = [ 'smw_id' => 1001 ] + $row;

		$this->assertEquals(
			(object)$expected,
			$instance->move( 42, 1001 )
		);
	}

	public function testChange_IdSubject_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( 'SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$table->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->willReturn( [ '_foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [ $table ] );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->willReturnCallback( static function ( $table, $vars, $conds ) {
				if ( isset( $conds['s_id'] ) && $conds['s_id'] === 42 ) {
					return true;
				}
				return false;
			} );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'update' )
			->willReturnCallback( static function ( $table, $set, $conds ) {
				// Accepts calls for s_id, p_id, _foo updates
			} );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001 );
	}

	public function testChange_IdSubject_PropertyNS_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( 'SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$table->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->willReturn( [ '_foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturnOnConsecutiveCalls( [ $table ] );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->willReturnCallback( static function ( $table, $vars, $conds ) {
				if ( isset( $conds['s_id'] ) && $conds['s_id'] === 42 ) {
					return true;
				}
				return false;
			} );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'update' )
			->willReturnCallback( static function ( $table, $set, $conds ) {
				// Accepts calls for s_id and _foo updates
			} );

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				[ 'p_id' => 42 ] );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001, SMW_NS_PROPERTY, NS_MAIN );
	}

	public function testChange_IdSubject_ConceptNS_Fields_NotFixedPropertyTable() {
		$table = $this->getMockBuilder( 'SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

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

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 's_id' => 42 ] )
			->willReturn( true );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'update' )
			->willReturnCallback( static function ( $table, $set, $conds ) {
				// Accepts calls for s_id and o_id updates
			} );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				[ 's_id' => 42 ] );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001, SMW_NS_CONCEPT, NS_MAIN );
	}

}
