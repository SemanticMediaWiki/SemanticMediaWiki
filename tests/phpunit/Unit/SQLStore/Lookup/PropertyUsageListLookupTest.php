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
	private $connection;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$tableLookup = new \SMW\SQLStore\Lookup\TableLookup(
			$this->connection
		);

		$servicesManager = new \SMW\Services\ServicesManager();

		$servicesManager->registerCallback( 'table.lookup', function() use( $tableLookup ) {
			return $tableLookup;
		} );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->will( $this->returnCallback( $servicesManager->returnCallback() ) );

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
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

	public function testLookupForMissingOptionsThrowsException() {

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->lookup();
	}

	/**
	 * @dataProvider usageCountProvider
	 */
	public function testLookupForValidProperty( $expectedCount ) {

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_id = 42;
		$row->usage_count = $expectedCount;

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->lookup();

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

	public function testLookupForInvalidProperty() {

		$row = new \stdClass;
		$row->smw_title = '-Foo';
		$row->smw_id = 42;
		$row->usage_count = 42;

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->requestOptions->limit = 1001;

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->lookup();

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
