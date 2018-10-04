<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\Options;
use SMW\SQLStore\EntityStore\PropertiesLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\PropertiesLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertiesLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertiesLookup::class,
			new PropertiesLookup( $store )
		);
	}

	public function testLookupForNonFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );
		$dataItem->setId( 42 );

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'execute' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getSQLOptions', 'getSQLConditions' ] )
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

		$instance = new PropertiesLookup(
			$store
		);

		$instance->fetchFromTable( $dataItem, $propertyTableDef );
	}

	public function testLookupForFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );
		$dataItem->setId( 1001 );

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'execute' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new PropertiesLookup(
			$store
		);

		$instance->fetchFromTable( $dataItem, $propertyTableDef );
	}

}
