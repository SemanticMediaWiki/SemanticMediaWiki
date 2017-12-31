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

	private $store;
	private $factory;

	protected function setUp() {

		$entityManager = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$entityManager->expects( $this->any() )
			->method( 'findAllEntitiesThatMatch' )
			->will( $this->returnValue( [] ) );

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityManager ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$propertyTableRowDiffer = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowDiffer' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableRowDiffer->expects( $this->any() )
			->method( 'computeTableRowDiff' )
			->will( $this->returnValue( [ [], [], [] ] ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
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

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'newChangeDiff' )
			->will( $this->returnValue( $changeDiff ) );

		$changeOp->expects( $this->any() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getDataOps' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( [] ) );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsStore' )
			->will( $this->returnValue( $propertyStatisticsStore ) );

		$this->factory->expects( $this->any() )
			->method( 'newHierarchyLookup' )
			->will( $this->returnValue( $hierarchyLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newSubobjectListFinder' )
			->will( $this->returnValue( $subobjectListFinder ) );

		$this->factory->expects( $this->any() )
			->method( 'newChangePropListener' )
			->will( $this->returnValue( $changePropListener ) );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyTableRowDiffer' )
			->will( $this->returnValue( $propertyTableRowDiffer ) );

		$this->factory->expects( $this->any() )
			->method( 'newChangeOp' )
			->will( $this->returnValue( $changeOp ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $this->store, $this->factory )
		);
	}

	public function testChangeTitleForMainNamespaceWithoutRedirectId() {

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->any() )
			->method( 'findAllEntitiesThatMatch' )
			->will( $this->returnValue( [] ) );

		$objectIdGenerator->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 5 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'findRedirect' )
			->will( $this->returnValue( 0 ) );

		$objectIdGenerator->expects( $this->never() )
			->method( 'deleteRedirect' );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

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

		$objectIdGenerator->expects( $this->any() )
			->method( 'findAllEntitiesThatMatch' )
			->will( $this->returnValue( [] ) );

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
