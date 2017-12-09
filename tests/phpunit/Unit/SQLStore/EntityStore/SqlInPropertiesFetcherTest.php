<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Options;

/**
 * @covers \SMW\SQLStore\EntityStore\TraversalPropertyLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TraversalPropertyLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TraversalPropertyLookup::class,
			new TraversalPropertyLookup( $store )
		);
	}

	public function testfetchForNonFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( array( 'o_id' => 42 ) ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection', 'getDataItemHandlerForDIType', 'getSQLOptions', 'getSQLConditions' ) )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLConditions' )
			->will( $this->returnValue( '' ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TraversalPropertyLookup(
			$store,
			new Options( array( 'smwgEntityLookupFeatures' => SMW_ESTORE_IN_PROP ) )
		);

		$instance->fetch( $propertyTableDef, $dataItem );
	}

	public function testfetchForFixedPropertyTable() {

		$dataItem = DIWikiPage::newFromText( __METHOD__ );

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( array( 'o_id' => 42 ) ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection', 'getDataItemHandlerForDIType' ) )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TraversalPropertyLookup(
			$store,
			new Options( array( 'smwgEntityLookupFeatures' => SMW_ESTORE_IN_PROP ) )
		);

		$instance->fetch( $propertyTableDef, $dataItem );
	}

}
