<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\Utils\CircularReferenceGuard;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMWRequestOptions as RequestOptions;
use SMWSQLStore3;
use SMW\SQLStore\TableBuilder\TableBuilder;
use Onoi\MessageReporter\MessageReporterFactory;
use SMWSql3SmwIds as IdTableManager;
use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMW\SQLStore\EntityStore\CachedEntityLookup;
use SMW\SQLStore\EntityStore\DirectEntityLookup;

/**
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class SQLStoreFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var QueryEngineFactory
	 */
	private $queryEngineFactory;

	/**
	 * @since 2.2
	 *
	 * @param SMWSQLStore3 $store
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->queryEngineFactory = new QueryEngineFactory( $store );
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {
		return $this->queryEngineFactory->newQueryEngine();
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newSlaveQueryEngine() {
		return $this->newMasterQueryEngine();
	}

	/**
	 * @since 2.5
	 *
	 * @return IdTableManager
	 */
	public function newIdTableManager() {

		$idToDataItemMatchFinder = new IdToDataItemMatchFinder(
			$this->store->getConnection( 'mw.db' ),
			$this->applicationFactory->getIteratorFactory()
		);

		return new IdTableManager( $this->store, $idToDataItemMatchFinder );
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newMasterConceptCache() {

		$conceptCache = new ConceptCache(
			$this->store,
			$this->queryEngineFactory->newConceptQuerySegmentBuilder()
		);

		$conceptCache->setUpperLimit(
			$GLOBALS['smwgQMaxLimit']
		);

		return $conceptCache;
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newSlaveConceptCache() {
		return $this->newMasterConceptCache();
	}

	/**
	 * @since 2.2
	 *
	 * @return ListLookup
	 */
	public function newUsageStatisticsCachedListLookup() {

		$settings = $this->applicationFactory->getSettings();

		$usageStatisticsListLookup = new UsageStatisticsListLookup(
			$this->store,
			$this->newPropertyStatisticsTable()
		);

		return $this->newCachedListLookup(
			$usageStatisticsListLookup,
			$settings->get( 'smwgStatisticsCache' ),
			$settings->get( 'smwgStatisticsCacheExpiry' )
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newPropertyUsageCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$propertyUsageListLookup = new PropertyUsageListLookup(
			$this->store,
			$this->newPropertyStatisticsTable(),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$propertyUsageListLookup,
			$settings->get( 'smwgPropertiesCache' ),
			$settings->get( 'smwgPropertiesCacheExpiry' )
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newUnusedPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$unusedPropertyListLookup = new UnusedPropertyListLookup(
			$this->store,
			$this->newPropertyStatisticsTable(),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$unusedPropertyListLookup,
			$settings->get( 'smwgUnusedPropertiesCache' ),
			$settings->get( 'smwgUnusedPropertiesCacheExpiry' )
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newUndeclaredPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$undeclaredPropertyListLookup = new UndeclaredPropertyListLookup(
			$this->store,
			$settings->get( 'smwgPDefaultType' ),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$undeclaredPropertyListLookup,
			$settings->get( 'smwgWantedPropertiesCache' ),
			$settings->get( 'smwgWantedPropertiesCacheExpiry' )
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param ListLookup $listLookup
	 * @param boolean $useCache
	 * @param integer $cacheExpiry
	 *
	 * @return ListLookup
	 */
	public function newCachedListLookup( ListLookup $listLookup, $useCache, $cacheExpiry ) {

		$cacheFactory = $this->applicationFactory->newCacheFactory();

		$cacheOptions = $cacheFactory->newCacheOptions( array(
			'useCache' => $useCache,
			'ttl'      => $cacheExpiry
		) );

		$cachedListLookup = new CachedListLookup(
			$listLookup,
			$cacheFactory->newMediaWikiCompositeCache( $cacheFactory->getMainCacheType() ),
			$cacheOptions
		);

		$cachedListLookup->setCachePrefix( $cacheFactory->getCachePrefix() );

		return $cachedListLookup;
	}

	/**
	 * @since 2.4
	 *
	 * @return DeferredCallableUpdate
	 */
	public function newDeferredCallableCachedListLookupUpdate() {

		$transactionalDeferredCallableUpdate = ApplicationFactory::getInstance()->newTransactionalDeferredCallableUpdate( function() {
			$this->newPropertyUsageCachedListLookup()->deleteCache();
			$this->newUnusedPropertyCachedListLookup()->deleteCache();
			$this->newUndeclaredPropertyCachedListLookup()->deleteCache();
			$this->newUsageStatisticsCachedListLookup()->deleteCache();
		} );

		return $transactionalDeferredCallableUpdate;
	}

	/**
	 * @since 2.3
	 *
	 * @return EntityRebuildDispatcher
	 */
	public function newEntityRebuildDispatcher() {
		return new EntityRebuildDispatcher( $this->store );
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup() {

		$settings = $this->applicationFactory->getSettings();
		$directEntityLookup = new DirectEntityLookup( $this->store );

		if ( $settings->get( 'smwgValueLookupCacheType' ) === CACHE_NONE ) {
			return $directEntityLookup;
		}

		$circularReferenceGuard = new CircularReferenceGuard( 'vl:store' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$cacheFactory = $this->applicationFactory->newCacheFactory();

		$blobStore = $cacheFactory->newBlobStore(
			'smw:vl:store',
			$settings->get( 'smwgValueLookupCacheType' ),
			$settings->get( 'smwgValueLookupCacheLifetime' )
		);

		$cachedEntityLookup = new CachedEntityLookup(
			$directEntityLookup,
			new RedirectTargetLookup( $this->store, $circularReferenceGuard ),
			$blobStore
		);

		$cachedEntityLookup->setCachedLookupFeatures(
			$settings->get( 'smwgValueLookupFeatures' )
		);

		return $cachedEntityLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @return RequestOptionsProcessor
	 */
	public function newRequestOptionsProcessor() {
		return new RequestOptionsProcessor( $this->store );
	}

	/**
	 * @since 2.3
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher() {

		$settings = $this->applicationFactory->getSettings();

		$propertyTableInfoFetcher = new PropertyTableInfoFetcher(
			new PropertyTypeFinder( $this->store->getConnection( 'mw.db' ) )
		);

		$propertyTableInfoFetcher->setCustomFixedPropertyList(
			$settings->get( 'smwgFixedProperties' )
		);

		$propertyTableInfoFetcher->setCustomSpecialPropertyList(
			$settings->get( 'smwgPageSpecialProperties' )
		);

		return $propertyTableInfoFetcher;
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertyTableIdReferenceFinder
	 */
	public function newPropertyTableIdReferenceFinder() {

		$propertyTableIdReferenceFinder = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$propertyTableIdReferenceFinder->isCapitalLinks(
			$GLOBALS['wgCapitalLinks']
		);

		return $propertyTableIdReferenceFinder;
	}

	/**
	 * @since 2.5
	 *
	 * @return Installer
	 */
	public function newInstaller() {

		$messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
		$options = $this->store->getOptions();

		$tableBuilder = TableBuilder::factory(
			$this->store->getConnection( DB_MASTER )
		);

		$tableBuilder->setMessageReporter(
			$messageReporter
		);

		$tableIntegrityExaminer = new TableIntegrityExaminer(
			$this->store
		);

		$tableSchemaManager = new TableSchemaManager(
			$this->store
		);

		$installer = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		if ( $options->has( Installer::OPT_MESSAGEREPORTER ) ) {
			$installer->setMessageReporter( $options->get( Installer::OPT_MESSAGEREPORTER ) );
		}

		$installer->isFromExtensionSchemaUpdate(
			( $options->has( 'isFromExtensionSchemaUpdate' ) ? $options->get( 'isFromExtensionSchemaUpdate' ) : false )
		);

		return $installer;
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemHandlerDispatcher
	 */
	public function newDataItemHandlerDispatcher() {
		return new DataItemHandlerDispatcher( $this->store );
	}

	/**
	 * @since 2.5
	 *
	 * @return LoggerInterface
	 */
	public function getLogger() {
		return ApplicationFactory::getInstance()->getMediaWikiLogger();
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyStatisticsTable
	 */
	public function newPropertyStatisticsTable() {

		$propertyStatisticsTable = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		$propertyStatisticsTable->setLogger(
			$this->getLogger()
		);

		$propertyStatisticsTable->isCommandLineMode(
			$GLOBALS['wgCommandLineMode']
		);

		return $propertyStatisticsTable;
	}

}
