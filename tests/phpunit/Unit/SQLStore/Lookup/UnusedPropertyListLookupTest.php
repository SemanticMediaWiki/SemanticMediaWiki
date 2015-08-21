<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\Lookup\UnusedPropertyListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UnusedPropertyListLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertyStatisticsTable;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\UnusedPropertyListLookup',
			new UnusedPropertyListLookup( $this->store, $this->propertyStatisticsTable, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable,
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
			'UnusedPropertyListLookup',
			$instance->getLookupIdentifier()
		);
	}

	public function testLookupIdentifierChangedByRequestOptions() {

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable,
			$this->requestOptions
		);

		$lookupIdentifier = $instance->getLookupIdentifier();

		$this->assertContains(
			'UnusedPropertyListLookup',
			$lookupIdentifier
		);

		$this->requestOptions->limit = 100;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable,
			$this->requestOptions
		);

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

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable
		);

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

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable,
			$this->requestOptions
		);

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

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions->limit = 1001;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsTable,
			$this->requestOptions
		);

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
