<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\Options;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertySubjectsLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertySubjectsLookup::class,
			new PropertySubjectsLookup( $store )
		);
	}

	public function testLookupForNonFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( [ 'o_id' => 42 ] ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType', 'getSQLOptions', 'getSQLConditions' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLConditions' )
			->will( $this->returnValue( '' ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

	public function testLookupForFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( [ 'o_id' => 42 ] ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( $resultWrapper ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

}
