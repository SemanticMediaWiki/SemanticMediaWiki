<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\DIProperty;
use SMW\MediaWiki\Api\Browse\PValueLookup;
use SMW\MediaWiki\Connection\Query;
use SMW\Tests\PHPUnitCompat;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PValueLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'ProximityPropertyValueLookup' )
			->willReturn( new \SMW\SQLStore\Lookup\ProximityPropertyValueLookup( $this->store ) );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PValueLookup::class,
			new PValueLookup( $this->store )
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
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $query );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PValueLookup(
			$this->store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			[
				'Test'
			],
			$res['query']
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
			->willReturn( $query );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->with( new DIProperty( 'Foobar' ) )
			->willReturn( 42 );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PValueLookup(
			$this->store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar.Foobar'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			[
				'Test'
			],
			$res['query']
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
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $query );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getLabelField' )
			->willReturn( 'o_hash' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PValueLookup(
			$this->store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Text'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			[
				'Test'
			],
			$res['query']
		);

		$this->assertContains(
			'[{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %FOO%"},{"AND":"p_id=42"}]',
			$query->__toString()
		);
	}

}
