<?php

namespace SMW\Tests\SQLStore\Writer;

use SMWSQLStore3Writers;
use Title;

/**
 * @covers \SMWSQLStore3Writers
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-sqlstore
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class ChangeTitleTest extends \PHPUnit_Framework_TestCase {

	private $factory;

	protected function setUp() {

		$propertyStatisticsTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsTable' )
			->will( $this->returnValue( $propertyStatisticsTable ) );
	}

	public function testCanConstruct() {

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $parentStore, $this->factory )
		);
	}

	public function testChangeTitleForMainNamespaceWithoutRedirectId() {

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 5 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'findRedirectIdFor' )
			->will( $this->returnValue( 0 ) );

		$objectIdGenerator->expects( $this->never() )
			->method( 'deleteRedirectEntry' );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( true ) );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SMWSQLStore3Writers( $parentStore, $this->factory );

		$instance->changeTitle(
			Title::newFromText( __METHOD__ . '-old', NS_MAIN ),
			Title::newFromText( __METHOD__ . '-new', NS_MAIN ),
			9999
		);
	}

	public function testChangeTitleForMainNamespaceWithRedirectId() {

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 5 ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( true ) );

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SMWSQLStore3Writers( $parentStore, $this->factory );

		$instance->changeTitle(
			Title::newFromText( __METHOD__ . '-old', NS_MAIN ),
			Title::newFromText( __METHOD__ . '-new', NS_MAIN ),
			9999,
			1111
		);
	}

}
