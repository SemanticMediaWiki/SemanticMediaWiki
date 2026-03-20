<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage;
use SMW\HierarchyLookup;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\QueryEngine;
use SMW\Services\ServicesContainer;
use SMW\SortLetter;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\ConceptCache;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\EntityLookup;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\Installer;
use SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\DisplayTitleLookup;
use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\Lookup\SingleEntityQueryLookup;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\PropertyTableUpdater;
use SMW\SQLStore\PropertyTypeFinder;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\RedirectUpdater;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\SQLStoreUpdater;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\Database;

/**
 * @covers \SMW\SQLStore\SQLStoreFactory
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactoryTest extends TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'HierarchyLookup', $hierarchyLookup );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SQLStoreFactory::class,
			new SQLStoreFactory( $this->store )
		);
	}

	public function testCanConstructUpdater() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			SQLStoreUpdater::class,
			$instance->newUpdater()
		);
	}

	public function testCanConstructSlaveQueryEngine() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			QueryEngine::class,
			$instance->newSlaveQueryEngine()
		);
	}

	public function testCanConstructMasterQueryEngine() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			QueryEngine::class,
			$instance->newMasterQueryEngine()
		);
	}

	public function testCanConstructMasterConceptCache() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			ConceptCache::class,
			$instance->newMasterConceptCache()
		);
	}

	public function testCanConstructSlaveConceptCache() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			ConceptCache::class,
			$instance->newSlaveConceptCache()
		);
	}

	public function testCanConstructEntityIdManager() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			EntityIdManager::class,
			$instance->newEntityIdManager()
		);

		$this->assertInstanceOf(
			EntityIdManager::class,
			$instance->newEntityIdManager()
		);
	}

	public function testCanConstructUsageStatisticsCachedListLookup() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			CachedListLookup::class,
			$instance->newUsageStatisticsCachedListLookup()
		);
	}

	public function testCanConstructPropertyUsageCachedListLookup() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			CachedListLookup::class,
			$instance->newPropertyUsageCachedListLookup( null )
		);
	}

	public function testCanConstructUnusedPropertyCachedListLookup() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			CachedListLookup::class,
			$instance->newUnusedPropertyCachedListLookup( null )
		);
	}

	public function testCanConstructUndeclaredPropertyCachedListLookup() {
		$instance = new SQLStoreFactory( new SQLStore() );

		$this->assertInstanceOf(
			CachedListLookup::class,
			$instance->newUndeclaredPropertyCachedListLookup( null, '_foo' )
		);
	}

	public function testCanConstructCachedListLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			CachedListLookup::class,
			$instance->newCachedListLookup( $listLookup, true, 42 )
		);
	}

	public function testCanConstructEntityLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			EntityLookup::class,
			$instance->newEntityLookup()
		);
	}

	public function testCanConstructPropertyTableIdReferenceFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableIdReferenceFinder::class,
			$instance->newPropertyTableIdReferenceFinder()
		);
	}

	public function testCanConstructDataItemHandlerFactory() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			DataItemHandlerFactory::class,
			$instance->newDataItemHandlerFactory()
		);
	}

	public function testCanConstructDeferredCachedListLookupUpdate() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			CallableUpdate::class,
			$instance->newDeferredCallableCachedListLookupUpdate()
		);
	}

	public function testCanConstructInstaller() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $store );

		$this->assertInstanceOf(
			Installer::class,
			$instance->newInstaller()
		);
	}

	public function testGetLogger() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			LoggerInterface::class,
			$instance->getLogger()
		);
	}

	public function testCanConstrucTraversalPropertyLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			TraversalPropertyLookup::class,
			$instance->newTraversalPropertyLookup()
		);
	}

	public function testCanConstrucPropertySubjectsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertySubjectsLookup::class,
			$instance->newPropertySubjectsLookup()
		);
	}

	public function testCanConstructPropertyStatisticsStore() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyStatisticsStore::class,
			$instance->newPropertyStatisticsStore()
		);
	}

	public function testCanConstructIdCacheManager() {
		$instance = new SQLStoreFactory( $this->store );

		$params = [
			'entity.id' => '',
			'entity.sort' => '',
			'entity.lookup' => '',
			'propertytable.hash' => ''
		];

		$this->assertInstanceOf(
			IdCacheManager::class,
			$instance->newIdCacheManager( 'foo', $params )
		);
	}

	public function testCanConstrucPropertyTableRowDiffer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableRowDiffer::class,
			$instance->newPropertyTableRowDiffer()
		);
	}

	public function testCanConstructIdEntityFinder() {
		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			IdEntityFinder::class,
			$instance->newIdEntityFinder( $idCacheManager )
		);
	}

	public function testCanConstructSequenceMapFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			SequenceMapFinder::class,
			$instance->newSequenceMapFinder( $idCacheManager )
		);
	}

	public function testCanConstructCacheWarmer() {
		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			CacheWarmer::class,
			$instance->newCacheWarmer( $idCacheManager )
		);
	}

	public function testCanConstructIdChanger() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			IdChanger::class,
			$instance->newIdChanger()
		);
	}

	public function testCanConstructDuplicateFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			DuplicateFinder::class,
			$instance->newDuplicateFinder()
		);
	}

	public function testCanConstructPropertyChangeListener() {
		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyChangeListener::class,
			$instance->newPropertyChangeListener()
		);
	}

	public function testCanConstructSubobjectListFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			SubobjectListFinder::class,
			$instance->newSubobjectListFinder()
		);
	}

	public function testCanConstructSemanticDataLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			CachingSemanticDataLookup::class,
			$instance->newSemanticDataLookup()
		);
	}

	public function testCanConstructTableFieldUpdater() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			TableFieldUpdater::class,
			$instance->newTableFieldUpdater()
		);
	}

	public function testCanConstructRedirectStore() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			RedirectStore::class,
			$instance->newRedirectStore()
		);
	}

	public function testCanConstructChangeOp() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ChangeOp::class,
			$instance->newChangeOp( $subject )
		);
	}

	public function testCanConstructProximityPropertyValueLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ProximityPropertyValueLookup::class,
			$instance->newProximityPropertyValueLookup()
		);
	}

	public function testCanConstructEntityUniquenessLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			EntityUniquenessLookup::class,
			$instance->newEntityUniquenessLookup()
		);
	}

	public function testCanConstructQueryDependencyLinksStoreFactory() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			QueryDependencyLinksStoreFactory::class,
			$instance->newQueryDependencyLinksStoreFactory()
		);
	}

	public function testCanConstructSortLetter() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			SortLetter::class,
			$instance->newSortLetter()
		);
	}

	public function testCanConstructPropertyTableIdReferenceDisposer() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableIdReferenceDisposer::class,
			$instance->newPropertyTableIdReferenceDisposer()
		);
	}

	public function testCanConstructPropertyTableHashes() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableHashes::class,
			$instance->newPropertyTableHashes( $idCacheManager )
		);
	}

	public function testCanConstructMissingRedirectLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			MissingRedirectLookup::class,
			$instance->newMissingRedirectLookup()
		);
	}

	public function testCanConstructMonolingualTextLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			MonolingualTextLookup::class,
			$instance->newMonolingualTextLookup()
		);
	}

	public function testCanConstructDisplayTitleLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			DisplayTitleLookup::class,
			$instance->newDisplayTitleLookup()
		);
	}

	public function testCanConstructPrefetchItemLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PrefetchItemLookup::class,
			$instance->newPrefetchItemLookup()
		);
	}

	public function testCanConstructPropertyTypeFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTypeFinder::class,
			$instance->newPropertyTypeFinder()
		);
	}

	public function testCanConstructTableStatisticsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			TableStatisticsLookup::class,
			$instance->newTableStatisticsLookup()
		);
	}

	public function testCanConstructErrorLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ErrorLookup::class,
			$instance->newErrorLookup()
		);
	}

	public function testCanConstructServicesContainer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ServicesContainer::class,
			$instance->newServicesContainer()
		);
	}

	public function testCanConstructPropertyTableUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableUpdater::class,
			$instance->newPropertyTableUpdater()
		);
	}

	public function testCanConstructPropertyTableInfoFetcher() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableInfoFetcher::class,
			$instance->newPropertyTableInfoFetcher()
		);
	}

	public function testCanConstructTraversalPropertyLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			TraversalPropertyLookup::class,
			$instance->newTraversalPropertyLookup()
		);
	}

	public function testCanConstructPropertySubjectsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertySubjectsLookup::class,
			$instance->newPropertySubjectsLookup()
		);
	}

	public function testCanConstructPropertiesLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertiesLookup::class,
			$instance->newPropertiesLookup()
		);
	}

	public function testCanConstructPropertyTableRowDiffer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PropertyTableRowDiffer::class,
			$instance->newPropertyTableRowDiffer()
		);
	}

	public function testCanConstructEntityIdFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			EntityIdFinder::class,
			$instance->newEntityIdFinder( $idCacheManager )
		);
	}

	public function testCanConstructRedirectUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			RedirectUpdater::class,
			$instance->newRedirectUpdater()
		);
	}

	public function testCanConstructPrefetchCache() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			PrefetchCache::class,
			$instance->newPrefetchCache()
		);
	}

	public function testCanConstructSingleEntityQueryLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			SingleEntityQueryLookup::class,
			$instance->newSingleEntityQueryLookup()
		);
	}

	public function testCanConstructDeferredCallableCachedListLookupUpdate() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			TransactionalCallableUpdate::class,
			$instance->newDeferredCallableCachedListLookupUpdate()
		);
	}

	public function testCanConstructRebuilder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			Rebuilder::class,
			$instance->newRebuilder()
		);
	}

	public function testCanConstructAuxiliaryFields() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			AuxiliaryFields::class,
			$instance->newAuxiliaryFields( $idCacheManager )
		);
	}

	public function testCanConstructRedirectTargetLookup() {
		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			RedirectTargetLookup::class,
			$instance->newRedirectTargetLookup( $idCacheManager )
		);
	}

	public function testCanConstructByGroupPropertyValuesLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ByGroupPropertyValuesLookup::class,
			$instance->newByGroupPropertyValuesLookup()
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {
		// Available class methods to be tested
		$classMethods = get_class_methods( SQLStoreFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep( '/^testCanConstruct/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testCanConstruct', 'new', $testMethods )
		);

		foreach ( $classMethods as $name ) {

			if ( substr( $name, 0, 3 ) !== 'new' ) {
				continue;
			}

			$this->assertArrayHasKey( $name, $testMethods );
		}
	}

}
