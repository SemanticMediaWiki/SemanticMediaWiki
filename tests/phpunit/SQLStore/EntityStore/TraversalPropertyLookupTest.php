<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\EntityStore\TraversalPropertyLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TraversalPropertyLookupTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TraversalPropertyLookup::class,
			new TraversalPropertyLookup( $store )
		);
	}

	public function testlookupForNonFixedPropertyTable() {
		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'smw_table' );

		// Mock the subquery builder
		$subqueryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$subqueryBuilder->expects( $this->once() )
			->method( 'select' )
			->with( 'p_id' )
			->willReturnSelf();

		$subqueryBuilder->expects( $this->once() )
			->method( 'from' )
			->with( 'smw_table' )
			->willReturnSelf();

		$subqueryBuilder->expects( $this->atLeastOnce() )
			->method( 'where' )
			->willReturnSelf();

		// Mock the main query builder
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder->expects( $this->once() )
			->method( 'newSubquery' )
			->willReturn( $subqueryBuilder );

		$queryBuilder->expects( $this->once() )
			->method( 'from' )
			->with( 'smw_object_ids' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'join' )
			->willReturnSelf();

		$queryBuilder->expects( $this->atLeastOnce() )
			->method( 'where' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'select' )
			->with( 'smw_title,smw_sortkey,smw_iw' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'distinct' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'caller' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'fetchResultSet' )
			->willReturn( [] );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->with( 'read' )
			->willReturn( $queryBuilder );

		$connection->expects( $this->atLeastOnce() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) {
				return "'$value'";
			} );

		$connection->expects( $this->atLeastOnce() )
			->method( 'applySqlOptions' )
			->willReturnSelf();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType', 'getSQLOptions', 'getSQLConditions' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLConditions' )
			->willReturn( '' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TraversalPropertyLookup( $store );
		$instance->fetchFromTable( $propertyTableDef, $dataItem );
	}

	public function testlookupForFixedPropertyTable() {
		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( 1 );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'smw_table' );

		$propertyTableDef->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		// Mock the query builder
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder->expects( $this->once() )
			->method( 'from' )
			->with( 'smw_table', 't1' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'select' )
			->with( 's_id' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'limit' )
			->with( 1 )
			->willReturnSelf();

		$queryBuilder->expects( $this->atLeastOnce() )
			->method( 'where' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'caller' )
			->willReturnSelf();

		$queryBuilder->expects( $this->once() )
			->method( 'fetchResultSet' )
			->willReturn( $resultWrapper );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->with( 'read' )
			->willReturn( $queryBuilder );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TraversalPropertyLookup( $store );
		$instance->fetchFromTable( $propertyTableDef, $dataItem );
	}
}
