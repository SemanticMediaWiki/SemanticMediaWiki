<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\DIProperty;

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
			$instance->isCached()
		);

		$this->assertContains(
			'smwgPropertiesCache',
			$instance->getLookupIdentifier()
		);
	}

	public function testLookupIdentifierChangedByRequestOptions() {

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$lookupIdentifier = $instance->getLookupIdentifier();

		$this->assertContains(
			'smwgPropertiesCache',
			$lookupIdentifier
		);

		$this->requestOptions->limit = 100;

		$instance = new PropertyUsageListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$this->assertContains(
			'smwgPropertiesCache',
			$instance->getLookupIdentifier()
		);

		$this->assertNotSame(
			$lookupIdentifier,
			$instance->getLookupIdentifier()
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
	public function testfetchListForValidProperty( $usageCounts, $expectedCount ) {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCounts' )
			->will( $this->returnValue( $usageCounts ) );

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

		$expected = array(
			new DIProperty( 'Foo' ),
			$expectedCount
		);

		$this->assertEquals(
			array( $expected ),
			$result
		);
	}

	public function testfetchListForInvalidProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$row = new \stdClass;
		$row->smw_title = '-Foo';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCounts' )
			->will( $this->returnValue( array() ) );

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

		// No match
		$provider[] = array(
			array(),
			0
		);

		// No match
		$provider[] = array(
			array( 99 => 1001 ),
			0
		);

		// Is a match
		$provider[] = array(
			array( '42' => 1001 ),
			1001
		);

		// Is a match
		$provider[] = array(
			array( 42 => 1001 ),
			1001
		);

		return $provider;
	}

}
