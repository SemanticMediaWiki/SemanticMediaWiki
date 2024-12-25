<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\UnusedPropertyListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UnusedPropertyListLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\UnusedPropertyListLookup',
			new UnusedPropertyListLookup( $this->store, $this->propertyStatisticsStore, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {
		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$this->assertIsString(

			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isFromCache()
		);

		$this->assertContains(
			'UnusedPropertyListLookup',
			$instance->getHash()
		);
	}

	public function testLookupIdentifierChangedByRequestOptions() {
		$requestOptions = new RequestOptions();

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$lookupIdentifier = $instance->getHash();

		$requestOptions->limit = 100;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$this->assertNotSame(
			$lookupIdentifier,
			$instance->getHash()
		);
	}

	public function testTryTofetchListForMissingOptionsThrowsException() {
		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		$this->expectException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testfetchListForValidProperty() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$row = new \stdClass;
		$row->smw_title = 'Foo';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$expected = [
			new DIProperty( 'Foo' )
		];

		$this->assertEquals(
			$expected,
			$result
		);
	}

	public function testfetchListForInvalidProperty() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$row = new \stdClass;
		$row->smw_title = '-Foo';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 'ORDER BY' => 'smw_sort', 'LIMIT' => 1001, 'OFFSET' => 0 ] ),
				$this->anything() )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		// TODO: Illegal dynamic property (#5421)
		$this->requestOptions->limit = 1001;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$this->assertInstanceOf(
			'\SMWDIError',
			$result[0]
		);
	}

}
