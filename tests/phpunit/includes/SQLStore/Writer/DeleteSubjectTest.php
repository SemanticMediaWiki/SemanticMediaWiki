<?php

namespace SMW\Tests\SQLStore\Writer;

use SMWSQLStore3Writers;
use Title;

/**
 * @covers \SMWSQLStore3Writers
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class DeleteSubjectTest extends \PHPUnit_Framework_TestCase {

	private $factory;
	private $store;

	protected function setUp() {

		$propertyStatisticsTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsTable' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\SubobjectListFinder' )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder->expects( $this->any() )
			->method( 'find' )
			->will( $this->returnValue( [] ) );

		$changePropListener = $this->getMockBuilder( '\SMW\ChangePropListener' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsTable' )
			->will( $this->returnValue( $propertyStatisticsTable ) );

		$this->factory->expects( $this->any() )
			->method( 'newHierarchyLookup' )
			->will( $this->returnValue( $hierarchyLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newSubobjectListFinder' )
			->will( $this->returnValue( $subobjectListFinder ) );

		$this->factory->expects( $this->any() )
			->method( 'newChangePropListener' )
			->will( $this->returnValue( $changePropListener ) );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $this->store, $this->factory )
		);
	}

	public function testDeleteSubjectForMainNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->once() )
			->method( 'getListOfIdMatchesFor' )
			->will( $this->returnValue( array( 0 ) ) );

		$objectIdGenerator->expects( $this->once() )
			->method( 'getPropertyTableHashes' )
			->will( $this->returnValue( array() ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->exactly( 7 ) )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$this->store->expects( $this->exactly( 4 ) )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SMWSQLStore3Writers( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

	public function testDeleteSubjectForConceptNamespace() {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->once() )
			->method( 'getListOfIdMatchesFor' )
			->with(
				$this->equalTo( $title->getDBkey() ),
				$this->equalTo( $title->getNamespace() ),
				$this->equalTo( $title->getInterwiki() ),
				'' )
			->will( $this->returnValue( array( 0 ) ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$database->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->will( $this->returnValue( true ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$this->store->expects( $this->exactly( 7 ) )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$this->store->expects( $this->exactly( 4 ) )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SMWSQLStore3Writers( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

}
