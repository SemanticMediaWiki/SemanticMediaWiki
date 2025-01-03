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
class SingleEntityQueryLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $idTable;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );
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
			->willReturn( [] );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

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
			->willReturn( 1001 );

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->with( $dataItem )
			->willReturn( $dataItem );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getPrintrequests' )
			->willReturn( [] );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $valueDescription );

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
			->with( $dataItem )
			->willReturn( $dataItem );

		$dataItem->expects( $this->once() )
			->method( 'asBase' )
			->willReturn( $dataItem_base );

		$this->idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->with( $dataItem_base )
			->willReturn( 1001 );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getPrintrequests' )
			->willReturn( [] );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $valueDescription );

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
			->willReturn( [] );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 0 );

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $valueDescription );

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
