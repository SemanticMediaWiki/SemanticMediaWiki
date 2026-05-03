<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\EntityStore\TraversalPropertyLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TraversalPropertyLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TraversalPropertyLookup::class,
			new TraversalPropertyLookup( $store )
		);
	}

	public function testlookupForNonFixedPropertyTable() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$qb = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
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

		$instance = new TraversalPropertyLookup(
			$store
		);

		$instance->fetchFromTable( $propertyTableDef, $dataItem );
	}

	public function testlookupForFixedPropertyTable() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$qb = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TraversalPropertyLookup(
			$store
		);

		$instance->fetchFromTable( $propertyTableDef, $dataItem );
	}

}
