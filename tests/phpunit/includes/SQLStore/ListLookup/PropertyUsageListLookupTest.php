<?php

namespace SMW\Tests\SQLStore\ListLookup;

use SMW\SQLStore\ListLookup\PropertyUsageListLookup;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\ListLookup\PropertyUsageListLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class PropertyUsageListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookup\PropertyUsageListLookup',
			new PropertyUsageListLookup( $store, $propertyStatisticsStore, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, $requestOptions );

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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, $requestOptions );
		$lookupIdentifier = $instance->getLookupIdentifier();

		$this->assertContains(
			'smwgPropertiesCache',
			$lookupIdentifier
		);

		$requestOptions->limit = 100;
		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, $requestOptions );

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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, null );

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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCounts' )
			->will( $this->returnValue( $usageCounts ) );

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, $requestOptions );
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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCounts' )
			->will( $this->returnValue( array() ) );

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->limit = 1001;

		$instance = new PropertyUsageListLookup( $store, $propertyStatisticsStore, $requestOptions );
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
