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
	private $conection;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();

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
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'p_id' => 1001 ] ),
				$this->equalTo( [ 'p_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
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
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'p_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
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
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 1001 ] ),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [ 's_id' => 42 ] ) );

		$this->connection->expects( $this->at( 3 ) )
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
