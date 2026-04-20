<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertySubjectsLookupTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertySubjectsLookup::class,
			new PropertySubjectsLookup( $store )
		);
	}

	public function testLookupForNonFixedPropertyTable() {
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

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$connection->expects( $this->atLeastOnce() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [] ) );

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

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

	public function testLookupForFixedPropertyTable() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$resultWrapper = new FakeResultWrapper( [] );

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

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$connection->expects( $this->atLeastOnce() )
			->method( 'readQuery' )
			->willReturn( $resultWrapper );

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

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

}
