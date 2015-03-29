<?php

namespace SMW\Tests\SQLStore\ListLookup;

use SMW\SQLStore\ListLookup\UnusedPropertyListLookup;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\ListLookup\UnusedPropertyListLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UnusedPropertyListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookup\UnusedPropertyListLookup',
			new UnusedPropertyListLookup( $store, $propertyStatisticsStore, null )
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

		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, $requestOptions );

		$this->assertInternalType(
			'string',
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isCached()
		);

		$this->assertContains(
			'UnusedPropertyListLookup',
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

		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, $requestOptions );
		$lookupIdentifier = $instance->getLookupIdentifier();

		$this->assertContains(
			'UnusedPropertyListLookup',
			$lookupIdentifier
		);

		$requestOptions->limit = 100;
		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, $requestOptions );

		$this->assertContains(
			'UnusedPropertyListLookup',
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

		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, null );

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testfetchListForValidProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$row = new \stdClass;
		$row->smw_title = 'Foo';

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

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsTable' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, $requestOptions );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$expected = array(
			new DIProperty( 'Foo' )
		);

		$this->assertEquals(
			$expected,
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
				$this->equalTo( array( 'ORDER BY' => 'smw_sortkey', 'LIMIT' => 1001, 'OFFSET' => 0 ) ),
				$this->anything() )
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

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsTable' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->limit = 1001;

		$instance = new UnusedPropertyListLookup( $store, $propertyStatisticsStore, $requestOptions );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertInstanceOf(
			'\SMWDIError',
			$result[0]
		);
	}

}
