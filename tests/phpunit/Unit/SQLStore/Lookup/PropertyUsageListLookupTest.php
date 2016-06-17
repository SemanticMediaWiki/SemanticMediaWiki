<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;

/**
 * @covers \SMW\SQLStore\Lookup\PropertyUsageListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class PropertyUsageListLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\PropertyUsageListLookup',
			new PropertyUsageListLookup( $this->store, $this->propertyStatisticsStore, $this->requestOptions )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$this->assertInternalType(
			'string',
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isFromCache()
		);

		$this->assertContains(
			'PropertyUsageListLookup',
			$instance->getHash()
		);
	}

	public function testLookupIdentifierChangedByRequestOptions() {

		$requestOptions = new RequestOptions();

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$lookupIdentifier = $instance->getHash();

		$requestOptions->limit = 100;

		$instance = new PropertyUsageListLookup(
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

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchList();
	}

	/**
	 * @dataProvider usageCountProvider
	 */
	public function testfetchListForValidProperty( $expectedCount ) {

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_id = 42;
		$row->usage_count = $expectedCount;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$property = new DIProperty( 'Foo' );
		$property->id = 42;

		$expected = array(
			$property,
			$expectedCount
		);

		$this->assertEquals(
			array( $expected ),
			$result
		);
	}

	public function testfetchListForInvalidProperty() {

		$row = new \stdClass;
		$row->smw_title = '-Foo';
		$row->smw_id = 42;
		$row->usage_count = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->requestOptions->limit = 1001;

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertInstanceOf(
			'\SMWDIError',
			$result[0][0]
		);
	}

	public function usageCountProvider() {

		$provider[] = array(
			0
		);

		$provider[] = array(
			1001
		);

		return $provider;
	}

}
