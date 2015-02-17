<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\UsageStatisticsListLookup;

/**
 * @covers \SMW\SQLStore\UsageStatisticsListLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UsageStatisticsListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\UsageStatisticsListLookup',
			new UsageStatisticsListLookup( $store )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UsageStatisticsListLookup( $store );

		$this->assertInternalType(
			'string',
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isCached()
		);

		$this->assertEquals(
			'smwgStatisticsCache',
			$instance->getLookupIdentifier()
		);
	}

	public function testFetchResultListForInvalidTableThrowsException() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Bar' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => 'throwExceptionForMismatch' ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new UsageStatisticsListLookup( $store );

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchResultList();
	}

	/**
	 * @dataProvider bySegmentDataProvider
	 */
	public function testFetchResultList( $segment, $type ) {

		$row = new \stdClass;
		$row->o_hash = 42;
		$row->count = 1001;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new UsageStatisticsListLookup( $store );
		$result = $instance->fetchResultList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertArrayHasKey(
			$segment,
			$result
		);

		$this->assertInternalType(
			$type,
			$result[ $segment ]
		);
	}

	public function bySegmentDataProvider() {
		return array(
			array( 'OWNPAGE',      'integer' ),
			array( 'QUERY',        'integer' ),
			array( 'QUERYSIZE',    'integer' ),
			array( 'QUERYFORMATS', 'array'   ),
			array( 'CONCEPTS',     'integer' ),
			array( 'SUBOBJECTS',   'integer' ),
			array( 'DECLPROPS',    'integer' ),
			array( 'USEDPROPS',    'integer' ),
			array( 'PROPUSES',     'integer' )
		);
	}

}
