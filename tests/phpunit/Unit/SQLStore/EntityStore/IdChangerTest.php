<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\IdChanger;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\EntityStore\IdChanger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IdChangerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $connection;
	private $jobFactory;
	private $nullJob;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getConnection', 'getPropertyTables' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );
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
				$this->equalTo( '*' ),
				$this->equalTo( [ 'smw_id' => 42 ] ) )
			->will( $this->returnValue( false ) );

		$instance = new IdChanger(
			$this->store
		);

		$instance->move( 42 );
	}

	public function testMove_ZeroTarget() {

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'FOO',
			'smw_sort' =>'FOO',
			'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6'
		];

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->equalTo( '*' ),
				$this->equalTo( [ 'smw_id' => 42 ] ) )
			->will( $this->returnValue( (object)$row ) );

		$this->connection->expects( $this->once() )
			->method( 'nextSequenceValue' )
			->will( $this->returnValue( '__seq__' ) );

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id' => '__seq__' ] + $row ) );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->will( $this->returnValue( 9999 ) );

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id' => 42 ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->onConsecutiveCalls( [] ) );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->will( $this->returnValue( $this->nullJob ) );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = ['smw_id' => 9999 ] + $row;

		$this->assertEquals(
			(object)$expected,
			$instance->move( 42, 0 )
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
			'smw_sort' =>'FOO',
			'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6'
		];

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->equalTo( '*' ),
				$this->equalTo( [ 'smw_id' => 42 ] ) )
			->will( $this->returnValue( (object)$row ) );

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id' => 1001 ] + $row ) );

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id' => 42 ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->onConsecutiveCalls( [] ) );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->will( $this->returnValue( $this->nullJob ) );

		$instance = new IdChanger(
			$this->store,
			$this->jobFactory
		);

		$expected = ['smw_id' => 1001 ] + $row;

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
			->will( $this->returnValue( true ) );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->will( $this->returnValue( [ '_foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->onConsecutiveCalls( [ $table ] ) );

		$this->connection->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'p_id' => 1001 ] ),
				$this->equalTo( [ 'p_id' => 42 ] ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ '_foo' => 1001 ] ),
				$this->equalTo( [ '_foo' => 42 ] ) );

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
			->will( $this->returnValue( true ) );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->will( $this->returnValue( [ '_foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->onConsecutiveCalls( [ $table ] ) );

		$this->connection->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'p_id' => 42 ] ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ '_foo' => 1001 ] ),
				$this->equalTo( [ '_foo' => 42 ] ) );

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
			->will( $this->returnValue( true ) );

		$table->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$table->expects( $this->any() )
			->method( 'getFields' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->onConsecutiveCalls( [ $table ] ) );

		$this->connection->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'o_id' => 1001 ] ),
				$this->equalTo( [ 'o_id' => 42 ] ) );

		$instance = new IdChanger(
			$this->store
		);

		$instance->change( 42, 1001, SMW_NS_CONCEPT, NS_MAIN );
	}

}
