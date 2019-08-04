<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SQLStore\SQLStoreUpdater;
use Title;

/**
 * @covers \SMW\SQLStore\SQLStoreUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SQLStoreUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $factory;
	private $idTable;

	protected function setUp() {

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$propertyTableRowDiffer = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowDiffer' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableRowDiffer->expects( $this->any() )
			->method( 'computeTableRowDiff' )
			->will( $this->returnValue( [ [], [], [] ] ) );

		$propertyTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$redirectUpdater = $this->getMockBuilder( '\SMW\SQLStore\RedirectUpdater' )
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

		$semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->any() )
			->method( 'getTextItems' )
			->will( $this->returnValue( [] ) );

		$changeDiff->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( [] ) );

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
			->method( 'newRedirectUpdater' )
			->will( $this->returnValue( $redirectUpdater ) );

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
			->method( 'newPropertyTableUpdater' )
			->will( $this->returnValue( $propertyTableUpdater ) );

		$this->factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->will( $this->returnValue( $semanticDataLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newChangeOp' )
			->will( $this->returnValue( $changeOp ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SQLStoreUpdater::class,
			new SQLStoreUpdater( $this->store, $this->factory )
		);
	}

	public function testDoDataUpdateForMainNamespaceWithoutSubobject() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->setMethods( null )
			->getMock();

		$objectIdGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->will( $this->returnValue( [] ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForConceptNamespaceWithoutSubobject() {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->setMethods( null )
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->will( $this->returnValue( [] ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForMainNamespaceWithRedirect() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromTitle( $title ) ] ) );

		$objectIdGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testAtomicTransactionOnDataUpdate() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromTitle( $title ) ] ) );

		$objectIdGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'beginSectionTransaction' );

		$database->expects( $this->atLeastOnce() )
			->method( 'endSectionTransaction' );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDeleteSubjectForMainNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$this->idTable->expects( $this->atLeastOnce() )
			->method( 'findIdsByTitle' )
			->will( $this->returnValue( [ 0 ] ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableIdReferenceFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'PropertyTableIdReferenceFinder' ) )
			->will( $this->returnValue( $propertyTableIdReferenceFinder ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

	public function testDeleteSubjectForConceptNamespace() {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$propertyTableIdReferenceFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'PropertyTableIdReferenceFinder' ) )
			->will( $this->returnValue( $propertyTableIdReferenceFinder ) );

		$this->idTable->expects( $this->atLeastOnce() )
			->method( 'findIdsByTitle' )
			->with(
				$this->equalTo( $title->getDBkey() ),
				$this->equalTo( $title->getNamespace() ),
				$this->equalTo( $title->getInterwiki() ),
				'' )
			->will( $this->returnValue( [ 0 ] ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->will( $this->returnValue( true ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$instance = new SQLStoreUpdater( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

}
