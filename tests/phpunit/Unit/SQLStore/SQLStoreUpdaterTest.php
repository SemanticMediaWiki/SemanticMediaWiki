<?php

namespace SMW\Tests\Unit\SQLStore;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\MediaWiki\Connection\Database;
use SMW\Options;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\PropertyTableUpdater;
use SMW\SQLStore\RedirectUpdater;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\SQLStoreUpdater;

/**
 * @covers \SMW\SQLStore\SQLStoreUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class SQLStoreUpdaterTest extends TestCase {

	private $store;
	private $factory;
	private $idTable;
	private $redirectUpdater;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$propertyTableRowDiffer = $this->getMockBuilder( PropertyTableRowDiffer::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableRowDiffer->expects( $this->any() )
			->method( 'computeTableRowDiff' )
			->willReturn( [ [], [], [] ] );

		$propertyTableUpdater = $this->getMockBuilder( PropertyTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$this->redirectUpdater = $this->getMockBuilder( RedirectUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder = $this->getMockBuilder( SubobjectListFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$subobjectListFinder->expects( $this->any() )
			->method( 'find' )
			->willReturn( [] );

		$semanticDataLookup = $this->getMockBuilder( CachingSemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff = $this->getMockBuilder( ChangeDiff::class )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->any() )
			->method( 'getTextItems' )
			->willReturn( [] );

		$changeDiff->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
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

		$this->factory = $this->getMockBuilder( SQLStoreFactory::class )
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
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromTitle( $title ) ] )
			->setMethods( null )
			->getMock();

		$objectIdGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'beginSectionTransaction' )
			->with( SQLStore::UPDATE_TRANSACTION );

		$database->expects( $this->once() )
			->method( 'endSectionTransaction' )
			->with( SQLStore::UPDATE_TRANSACTION );
		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( SQLStore::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForConceptNamespaceWithoutSubobject() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, SMW_NS_CONCEPT );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromTitle( $title ) ] )
			->setMethods( null )
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [] );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( SQLStore::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForMainNamespaceWithRedirect() {
		$this->redirectUpdater->expects( $this->any() )
			->method( 'shouldCleanUpAnnotationsAndRedirects' )
			->willReturn( true );

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromTitle( $title ) ] )
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromTitle( $title ) ] );

		$objectIdGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( SQLStore::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testAtomicTransactionOnDataUpdate() {
		$this->redirectUpdater->expects( $this->any() )
			->method( 'shouldCleanUpAnnotationsAndRedirects' )
			->willReturn( true );

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromTitle( $title ) ] )
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromTitle( $title ) ] );

		$objectIdGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'beginSectionTransaction' );

		$database->expects( $this->atLeastOnce() )
			->method( 'endSectionTransaction' );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$parentStore = $this->getMockBuilder( SQLStore::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $parentStore, $this->factory );
		$instance->doDataUpdate( $semanticData );
	}

	public function testDeleteSubjectForMainNamespace() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_MAIN );

		$this->idTable->expects( $this->atLeastOnce() )
			->method( 'findIdsByTitle' )
			->willReturn( [ 0 ] );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableIdReferenceFinder = $this->getMockBuilder( PropertyTableIdReferenceFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

	public function testDeleteSubjectForConceptNamespace() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, SMW_NS_CONCEPT );

		$propertyTableIdReferenceFinder = $this->getMockBuilder( PropertyTableIdReferenceFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
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

		$database = $this->getMockBuilder( Database::class )
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
			->willReturn( new Options() );

		$instance = new SQLStoreUpdater( $this->store, $this->factory );
		$instance->deleteSubject( $title );
	}

}
