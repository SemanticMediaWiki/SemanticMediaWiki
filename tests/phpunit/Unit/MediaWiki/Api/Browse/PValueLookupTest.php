<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\DIProperty;
use SMW\MediaWiki\Api\Browse\PValueLookup;
use SMW\MediaWiki\Connection\Query;
use FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PValueLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PValueLookup::class,
			new PValueLookup( $store )
		);
	}

	public function testLookup_wpg_property() {

		$row = new \stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$query = new Query( $connection );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( new FakeResultWrapper( [ $row ] ) ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new PValueLookup(
			$store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$res['query'],
			[
				'Test'
			]
		);

		$this->assertContains(
			'[{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %FOO%"}]',
			$query->__toString()
		);
	}

	public function testLookup_wpg_propertyChain() {

		$row = new \stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( new FakeResultWrapper( [ $row ] ) ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->with(  $this->equalTo( new DIProperty( 'Foobar' ) ) )
			->will( $this->returnValue( 42 ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new PValueLookup(
			$store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar.Foobar'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$res['query'],
			[
				'Test'
			]
		);
	}

	public function testLookup_txt_property() {

		$row = new \stdClass;
		$row->o_hash = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$query = new Query( $connection );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( new FakeResultWrapper( [ $row ] ) ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getLabelField' )
			->will( $this->returnValue( 'o_hash' ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new PValueLookup(
			$store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Text'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$res['query'],
			[
				'Test'
			]
		);

		$this->assertContains(
			'[{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %FOO%"},{"AND":"p_id=42"}]',
			$query->__toString()
		);
	}

}
