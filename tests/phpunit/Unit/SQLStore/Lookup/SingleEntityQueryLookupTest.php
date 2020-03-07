<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\SingleEntityQueryLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\SingleEntityQueryLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class SingleEntityQueryLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $idTable;

	protected function setUp() : void {

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->idTable ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SingleEntityQueryLookup::class,
			new SingleEntityQueryLookup( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			new SingleEntityQueryLookup( $this->store )
		);
	}

	public function testNonValueDescriptionReturnsEmptyQueryResult() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ThingDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->any() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( [] ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$instance = new SingleEntityQueryLookup(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\Query\QueryResult',
			$instance->getQueryResult( $query )
		);
	}

	public function testGetQueryResult_PageEntity() {

		$this->idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 1001 ) );

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->with( $this->equalTo( $dataItem ) )
			->will( $this->returnValue( $dataItem ) );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( [] ) );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $valueDescription ) );

		$instance = new SingleEntityQueryLookup(
			$this->store
		);

		$res = $instance->getQueryResult( $query );

		$this->assertInstanceOf(
			'\SMW\Query\QueryResult',
			$res
		);

		$this->assertNotEmpty(
			$res->getResults()
		);
	}

	public function testGetQueryResult_SubobjectEntity() {

		$dataItem_base = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->with( $this->equalTo( $dataItem ) )
			->will( $this->returnValue( $dataItem ) );

		$dataItem->expects( $this->once() )
			->method( 'asBase' )
			->will( $this->returnValue( $dataItem_base ) );

		$this->idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->with( $this->equalTo( $dataItem_base ) )
			->will( $this->returnValue( 1001 ) );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( [] ) );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $valueDescription ) );

		$instance = new SingleEntityQueryLookup(
			$this->store
		);

		$res = $instance->getQueryResult( $query );

		$this->assertInstanceOf(
			'\SMW\Query\QueryResult',
			$res
		);

		$this->assertNotEmpty(
			$res->getResults()
		);
	}

	public function testGetQueryResult_LimitNull() {

		$this->idTable->expects( $this->never() )
			->method( 'findAssociatedRev' );

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( [] ) );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 0 ) );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $valueDescription ) );

		$instance = new SingleEntityQueryLookup(
			$this->store
		);

		$res = $instance->getQueryResult( $query );

		$this->assertInstanceOf(
			'\SMW\Query\QueryResult',
			$res
		);

		$this->assertEmpty(
			$res->getResults()
		);
	}

}
