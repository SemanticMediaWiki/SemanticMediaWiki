<?php

namespace SMW\Tests\SQLStore;

use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\Options;
use SMW\SQLStore\SQLStoreFactory;
use SMW\Tests\TestEnvironment;
use SMWSQLStore3;

/**
 * @covers \SMW\SQLStore\SQLStoreFactory
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactoryTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'HierarchyLookup', $hierarchyLookup );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStoreFactory',
			new SQLStoreFactory( $this->store )
		);
	}

	public function testCanConstructUpdater() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStoreUpdater',
			$instance->newUpdater()
		);
	}

	public function testCanConstructSlaveQueryEngine() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newSlaveQueryEngine()
		);
	}

	public function testCanConstructMasterQueryEngine() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newMasterQueryEngine()
		);
	}

	public function testCanConstructMasterConceptCache() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$instance->newMasterConceptCache()
		);
	}

	public function testCanConstructSlaveConceptCache() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$instance->newSlaveConceptCache()
		);
	}

	public function testCanConstructEntityIdManager() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMWSql3SmwIds',
			$instance->newEntityIdManager()
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\EntityIdManager',
			$instance->newEntityIdManager()
		);
	}

	public function testCanConstructUsageStatisticsCachedListLookup() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUsageStatisticsCachedListLookup()
		);
	}

	public function testCanConstructPropertyUsageCachedListLookup() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newPropertyUsageCachedListLookup( null )
		);
	}

	public function testCanConstructUnusedPropertyCachedListLookup() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUnusedPropertyCachedListLookup( null )
		);
	}

	public function testCanConstructUndeclaredPropertyCachedListLookup() {
		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUndeclaredPropertyCachedListLookup( null, '_foo' )
		);
	}

	public function testCanConstructCachedListLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newCachedListLookup( $listLookup, true, 42 )
		);
	}

	public function testCanConstructEntityLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\EntityLookup',
			$instance->newEntityLookup()
		);
	}

	public function testCanConstructPropertyTableIdReferenceFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\PropertyTableIdReferenceFinder',
			$instance->newPropertyTableIdReferenceFinder()
		);
	}

	public function testCanConstructDataItemHandlerFactory() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\DataItemHandlerFactory',
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
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $store );

		$this->assertInstanceOf(
			'SMW\SQLStore\Installer',
			$instance->newInstaller()
		);
	}

	public function testGetLogger() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\Psr\Log\LoggerInterface',
			$instance->getLogger()
		);
	}

	public function testCanConstrucTraversalPropertyLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\TraversalPropertyLookup',
			$instance->newTraversalPropertyLookup()
		);
	}

	public function testCanConstrucPropertySubjectsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PropertySubjectsLookup',
			$instance->newPropertySubjectsLookup()
		);
	}

	public function testCanConstructPropertyStatisticsStore() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyStatisticsStore',
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
			'\SMW\SQLStore\EntityStore\IdCacheManager',
			$instance->newIdCacheManager( 'foo', $params )
		);
	}

	public function testCanConstrucPropertyTableRowDiffer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowDiffer',
			$instance->newPropertyTableRowDiffer()
		);
	}

	public function testCanConstructIdEntityFinder() {
		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\IdEntityFinder',
			$instance->newIdEntityFinder( $idCacheManager )
		);
	}

	public function testCanConstructSequenceMapFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\SequenceMapFinder',
			$instance->newSequenceMapFinder( $idCacheManager )
		);
	}

	public function testCanConstructCacheWarmer() {
		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\CacheWarmer',
			$instance->newCacheWarmer( $idCacheManager )
		);
	}

	public function testCanConstructIdChanger() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\IdChanger',
			$instance->newIdChanger()
		);
	}

	public function testCanConstructDuplicateFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\DuplicateFinder',
			$instance->newDuplicateFinder()
		);
	}

	public function testCanConstructPropertyChangeListener() {
		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener',
			$instance->newPropertyChangeListener()
		);
	}

	public function testCanConstructSubobjectListFinder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\SubobjectListFinder',
			$instance->newSubobjectListFinder()
		);
	}

	public function testCanConstructSemanticDataLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\CachingSemanticDataLookup',
			$instance->newSemanticDataLookup()
		);
	}

	public function testCanConstructTableFieldUpdater() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableFieldUpdater',
			$instance->newTableFieldUpdater()
		);
	}

	public function testCanConstructRedirectStore() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\RedirectStore',
			$instance->newRedirectStore()
		);
	}

	public function testCanConstructChangeOp() {
		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$instance->newChangeOp( $subject )
		);
	}

	public function testCanConstructProximityPropertyValueLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\ProximityPropertyValueLookup',
			$instance->newProximityPropertyValueLookup()
		);
	}

	public function testCanConstructEntityUniquenessLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\EntityUniquenessLookup',
			$instance->newEntityUniquenessLookup()
		);
	}

	public function testCanConstructQueryDependencyLinksStoreFactory() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependencyLinksStoreFactory',
			$instance->newQueryDependencyLinksStoreFactory()
		);
	}

	public function testCanConstructSortLetter() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SortLetter',
			$instance->newSortLetter()
		);
	}

	public function testCanConstructPropertyTableIdReferenceDisposer() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableIdReferenceDisposer',
			$instance->newPropertyTableIdReferenceDisposer()
		);
	}

	public function testCanConstructPropertyTableHashes() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTable\PropertyTableHashes',
			$instance->newPropertyTableHashes( $idCacheManager )
		);
	}

	public function testCanConstructMissingRedirectLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\MissingRedirectLookup',
			$instance->newMissingRedirectLookup()
		);
	}

	public function testCanConstructMonolingualTextLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\MonolingualTextLookup',
			$instance->newMonolingualTextLookup()
		);
	}

	public function testCanConstructDisplayTitleLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\DisplayTitleLookup',
			$instance->newDisplayTitleLookup()
		);
	}

	public function testCanConstructPrefetchItemLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PrefetchItemLookup',
			$instance->newPrefetchItemLookup()
		);
	}

	public function testCanConstructPropertyTypeFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTypeFinder',
			$instance->newPropertyTypeFinder()
		);
	}

	public function testCanConstructTableStatisticsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\TableStatisticsLookup',
			$instance->newTableStatisticsLookup()
		);
	}

	public function testCanConstructErrorLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\ErrorLookup',
			$instance->newErrorLookup()
		);
	}

	public function testCanConstructServicesContainer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\Services\ServicesContainer',
			$instance->newServicesContainer()
		);
	}

	public function testCanConstructPropertyTableUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableUpdater',
			$instance->newPropertyTableUpdater()
		);
	}

	public function testCanConstructPropertyTableInfoFetcher() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableInfoFetcher',
			$instance->newPropertyTableInfoFetcher()
		);
	}

	public function testCanConstructTraversalPropertyLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\TraversalPropertyLookup',
			$instance->newTraversalPropertyLookup()
		);
	}

	public function testCanConstructPropertySubjectsLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PropertySubjectsLookup',
			$instance->newPropertySubjectsLookup()
		);
	}

	public function testCanConstructPropertiesLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PropertiesLookup',
			$instance->newPropertiesLookup()
		);
	}

	public function testCanConstructPropertyTableRowDiffer() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowDiffer',
			$instance->newPropertyTableRowDiffer()
		);
	}

	public function testCanConstructEntityIdFinder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\EntityIdFinder',
			$instance->newEntityIdFinder( $idCacheManager )
		);
	}

	public function testCanConstructRedirectUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\RedirectUpdater',
			$instance->newRedirectUpdater()
		);
	}

	public function testCanConstructPrefetchCache() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PrefetchCache',
			$instance->newPrefetchCache()
		);
	}

	public function testCanConstructSingleEntityQueryLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\SingleEntityQueryLookup',
			$instance->newSingleEntityQueryLookup()
		);
	}

	public function testCanConstructDeferredCallableCachedListLookupUpdate() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Deferred\TransactionalCallableUpdate',
			$instance->newDeferredCallableCachedListLookupUpdate()
		);
	}

	public function testCanConstructRebuilder() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Rebuilder\Rebuilder',
			$instance->newRebuilder()
		);
	}

	public function testCanConstructAuxiliaryFields() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\AuxiliaryFields',
			$instance->newAuxiliaryFields( $idCacheManager )
		);
	}

	public function testCanConstructRedirectTargetLookup() {
		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\RedirectTargetLookup',
			$instance->newRedirectTargetLookup( $idCacheManager )
		);
	}

	public function testCanConstructByGroupPropertyValuesLookup() {
		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup',
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
