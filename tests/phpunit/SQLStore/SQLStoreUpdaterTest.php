<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SQLStore\SQLStoreUpdater;
use SMW\SQLStore\SQLStore;
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
class SQLStoreUpdaterTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $factory;
	private $idTable;
	private $redirectUpdater;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$propertyTableRowDiffer = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowDiffer' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableRowDiffer->expects( $this->any() )
			->method( 'computeTableRowDiff' )
			->willReturn( [ [], [], [] ] );

		$propertyTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
			->disableOriginalConstructor()
			->getMock();

		$this->redirectUpdater = $this->getMockBuilder( '\SMW\SQLStore\RedirectUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\SubobjectListFinder' )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder->expects( $this->any() )
			->method( 'find' )
			->willReturn( [] );

		$semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->any() )
			->method( 'getTextItems' )
			->willReturn( [] );

		$changeDiff->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'newChangeDiff' )
			->willReturn( $changeDiff );

		$changeOp->expects( $this->any() )
			->method( 'getChangedEntityIdSummaryList' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getDataOps' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->willReturn( [] );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newRedirectUpdater' )
			->willReturn( $this->redirectUpdater );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsStore' )
			->willReturn( $propertyStatisticsStore );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyChangeListener' )
			->willReturn( $propertyChangeListener );

		$this->factory->expects( $this->any() )
			->method( 'newSubobjectListFinder' )
			->willReturn( $subobjectListFinder );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyTableRowDiffer' )
			->willReturn( $propertyTableRowDiffer );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyTableUpdater' )
			->willReturn( $propertyTableUpdater );

		$this->factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->willReturn( $semanticDataLookup );

		$this->factory->expects( $this->any() )
			->method( 'newChangeOp' )
			->willReturn( $changeOp );
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
			->onlyMethods( [] )
			->getMock();

		$objectIdGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'beginSectionTransaction' )
			->with( SQLStore::UPDATE_TRANSACTION );

		$database->expects( $this->once() )
			->method( 'endSectionTransaction' )
			->with( SQLStore::UPDATE_TRANSACTION );
		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIdGenerator );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForConceptNamespaceWithoutSubobject() {
		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->onlyMethods( [] )
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

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
			->willReturn( $propertyTableInfoFetcher );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$parentStore->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForMainNamespaceWithRedirect() {
		$this->redirectUpdater->expects( $this->any() )
			->method( 'shouldCleanUpAnnotationsAndRedirects' )
			->willReturn( true );

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->onlyMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ DIWikiPage::newFromTitle( $title ) ] );

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
			->willReturn( $propertyTableInfoFetcher );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIdGenerator );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testAtomicTransactionOnDataUpdate() {
		$this->redirectUpdater->expects( $this->any() )
			->method( 'shouldCleanUpAnnotationsAndRedirects' )
			->willReturn( true );

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( [ DIWikiPage::newFromTitle( $title ) ] )
			->onlyMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ DIWikiPage::newFromTitle( $title ) ] );

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
			->willReturn( $propertyTableInfoFetcher );

		$parentStore->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIdGenerator );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$parentStore->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDeleteSubjectForMainNamespace() {
		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$this->idTable->expects( $this->atLeastOnce() )
			->method( 'findIdsByTitle' )
			->willReturn( [ 0 ] );

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
			->willReturn( $propertyTableInfoFetcher );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'PropertyTableIdReferenceFinder' )
			->willReturn( $propertyTableIdReferenceFinder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $database );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

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
			->willReturn( $propertyTableInfoFetcher );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'PropertyTableIdReferenceFinder' )
			->willReturn( $propertyTableIdReferenceFinder );

		$this->idTable->expects( $this->atLeastOnce() )
			->method( 'findIdsByTitle' )
			->with(
				$title->getDBkey(),
				$title->getNamespace(),
				$title->getInterwiki(),
				'' )
			->willReturn( [ 0 ] );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->willReturn( true );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $database );

		$this->store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$instance = new SQLStoreUpdater( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

}
