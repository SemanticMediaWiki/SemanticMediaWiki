<?php

namespace SMW\Tests\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\QueryResult;
use SMW\QueryEngine;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\SingleEntityQueryLookup;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\Lookup\SingleEntityQueryLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class SingleEntityQueryLookupTest extends TestCase {

	private $store;
	private $idTable;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
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
			QueryEngine::class,
			new SingleEntityQueryLookup( $this->store )
		);
	}

	public function testNonValueDescriptionReturnsEmptyQueryResult() {
		$description = $this->getMockBuilder( ThingDescription::class )
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
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testGetQueryResult_PageEntity() {
		$this->idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->willReturn( 1001 );

		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->with( $dataItem )
			->willReturn( $dataItem );

		$valueDescription = $this->getMockBuilder( ValueDescription::class )
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
			QueryResult::class,
			$res
		);

		$this->assertNotEmpty(
			$res->getResults()
		);
	}

	public function testGetQueryResult_SubobjectEntity() {
		$dataItem_base = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( WikiPage::class )
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

		$valueDescription = $this->getMockBuilder( ValueDescription::class )
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
			QueryResult::class,
			$res
		);

		$this->assertNotEmpty(
			$res->getResults()
		);
	}

	public function testGetQueryResult_LimitNull() {
		$this->idTable->expects( $this->never() )
			->method( 'findAssociatedRev' );

		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription = $this->getMockBuilder( ValueDescription::class )
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
			QueryResult::class,
			$res
		);

		$this->assertEmpty(
			$res->getResults()
		);
	}

}
