<?php

namespace SMW\Tests\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\UndeclaredPropertyListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UndeclaredPropertyListLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->willReturn( [] );
	}

	public function testCanConstruct() {
		$defaultPropertyType = '_foo';

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\UndeclaredPropertyListLookup',
			new UndeclaredPropertyListLookup( $this->store, $defaultPropertyType, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {
		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$this->requestOptions
		);

		$this->assertIsString(

			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isFromCache()
		);

		$this->assertContains(
			'UndeclaredPropertyListLookup',
			$instance->getHash()
		);
	}

	public function testNullRequestOptionsThrowsException() {
		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType
		);

		$this->expectException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testInvalidTableIdThrowsException() {
		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$this->requestOptions
		);

		$this->expectException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testLookupIdentifierChangedByRequestOptions() {
		$defaultPropertyType = '_foo';
		$requestOptions = new RequestOptions();

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$requestOptions
		);

		$lookupIdentifier = $instance->getHash();
		$requestOptions->limit = 100;

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$requestOptions
		);

		$this->assertNotSame(
			$lookupIdentifier,
			$instance->getHash()
		);
	}

	public function testfetchListForValidProperty() {
		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->count = 42;

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( '_foo' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$expected = [
			new DIProperty( 'Foo' ),
			42
		];

		$this->assertEquals(
			[ $expected ],
			$result
		);
	}

	public function testfetchListForInvalidProperty() {
		$row = new \stdClass;
		$row->smw_title = '-Foo';
		$row->count = 42;

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( '_foo' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$this->assertInstanceOf(
			'\SMWDIError',
			$result[0][0]
		);
	}

	public function testfetchListForFixedPropertyTable() {
		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'select' );

		$this->store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( '_foo' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$this->assertEmpty(
			$result
		);
	}

}
