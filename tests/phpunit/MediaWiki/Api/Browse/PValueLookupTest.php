<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\MediaWiki\Api\Browse\PValueLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PValueLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookupTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'ProximityPropertyValueLookup' )
			->willReturn( new ProximityPropertyValueLookup( $this->store ) );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PValueLookup::class,
			new PValueLookup( $this->store )
		);
	}

	public function testLookup_wpg_property() {
		$row = new stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( Database::class )
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
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
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

		$this->assertStringContainsString(
			'[{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %Foo%"},{"OR":"smw_sortkey LIKE %FOO%"}]',
			$query->__toString()
		);
	}

	public function testLookup_wpg_propertyChain() {
		$row = new stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
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
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->with( new Property( 'Foobar' ) )
			->willReturn( 42 );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
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
		$row = new stdClass;
		$row->o_hash = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( Database::class )
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
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
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

		$this->assertStringContainsString(
			'[{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %Foo%"},{"OR":"o_hash LIKE %FOO%"},{"AND":"p_id=42"}]',
			$query->__toString()
		);
	}

}
