<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\MediaWiki\Connection\Query;
use FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ProximityPropertyValueLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ProximityPropertyValueLookup::class,
			new ProximityPropertyValueLookup( $store )
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
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
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

		$instance = new ProximityPropertyValueLookup(
			$store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar'
		];

		$instance->lookup(
			new DIProperty( 'Bar' ),
			'Foo',
			new RequestOptions()
		);

		$this->assertContains(
			'[{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %FOO%"}]',
			$query->__toString()
		);
	}

	public function tesLookup_txt_property() {

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
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
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

		$instance = new ProximityPropertyValueLookup(
			$store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Text'
		];

		$instance->lookup(
			new DIProperty( '_TEXT' ),
			'Foo',
			new RequestOptions()
		);

		$this->assertContains(
			'[{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %FOO%"},{"AND":"p_id=42"}]',
			$query->__toString()
		);
	}

}
