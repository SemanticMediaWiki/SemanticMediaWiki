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
use SMW\Options;
use SMWSQLStore3;
use SMW\SQLStore\TableBuilder\TableBuilder;
use Onoi\MessageReporter\MessageReporterFactory;
use Onoi\Cache\Cache;
use SMWSql3SmwIds as EntityIdManager;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMW\SQLStore\EntityStore\CachedEntityLookup;
use SMW\SQLStore\EntityStore\DirectEntityLookup;
use SMW\SQLStore\EntityStore\SqlEntityLookupResultFetcher;
use SMW\ProcessLruCache;
use SMW\SQLStore\EntityStore\IdMatchFinder;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\ChangePropListener;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\ChangeOp\ChangeOp;

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
	 * @return EntityIdManager
	 */
	public function newEntityIdManager() {
		return new EntityIdManager( $this->store, $this );
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
			$settings->safeGet( 'special.statistics' ),
			$settings->safeGet( 'special.statistics' )
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
			$settings->safeGet( 'special.properties' ),
			$settings->safeGet( 'special.properties' )
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
			$settings->safeGet( 'special.unusedproperties' ),
			$settings->safeGet( 'special.unusedproperties' )
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
			$settings->safeGet( 'special.wantedproperties' ),
			$settings->safeGet( 'special.wantedproperties' )
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

		if ( is_int( $useCache ) ) {
			$useCache = true;
		}

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

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalUpdate( function() {
			$this->newPropertyUsageCachedListLookup()->deleteCache();
			$this->newUnusedPropertyCachedListLookup()->deleteCache();
			$this->newUndeclaredPropertyCachedListLookup()->deleteCache();
			$this->newUsageStatisticsCachedListLookup()->deleteCache();
		} );

		return $deferredTransactionalUpdate;
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
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher() {

		$settings = ApplicationFactory::getInstance()->getSettings();

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

		$settings = ApplicationFactory::getInstance()->getSettings();

		$messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();

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

		$tableSchemaManager->setFieldTypeFeatures(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		$installer = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$installer->setOptions(
			$this->store->getOptions()->filter(
				[
					Installer::OPT_MESSAGEREPORTER,
					Installer::OPT_TABLE_OPTIMZE,
					Installer::OPT_IMPORT,
					Installer::OPT_SCHEMA_UPDATE
				]
			)
		);

		return $installer;
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemHandlerDispatcher
	 */
	public function newDataItemHandlerDispatcher() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$dataItemHandlerDispatcher = new DataItemHandlerDispatcher(
			$this->store
		);

		$dataItemHandlerDispatcher->setFieldTypeFeatures(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		return $dataItemHandlerDispatcher;
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
	 * @since 3.0
	 *
	 * @return SqlEntityLookupResultFetcher
	 */
	public function newSqlEntityLookupResultFetcher() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$options = new Options(
			array(
				'smwgEntityLookupFeatures' => $settings->get( 'smwgEntityLookupFeatures' )
			)
		);

		return new SqlEntityLookupResultFetcher( $this->store, $options );
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

	/**
	 * @since 3.0
	 *
	 * @return ProcessLruCache
	 */
	public function newProcessLruCache( array $config ) {

		$processLruCache = ProcessLruCache::newFromConfig( $config );
		$processLruCache->reset();

		return $processLruCache;
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyTableRowDiffer
	 */
	public function newPropertyTableRowDiffer() {
		return new PropertyTableRowDiffer( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 *
	 * @return IdMatchFinder
	 */
	public function newIdMatchFinder( Cache $cache ) {

		$idMatchFinder = new IdMatchFinder(
			$this->store->getConnection( 'mw.db' ),
			$this->applicationFactory->getIteratorFactory(),
			$cache
		);

		return $idMatchFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return HierarchyLookup
	 */
	public function newHierarchyLookup() {
		return $this->applicationFactory->newHierarchyLookup();
	}

	/**
	 * @since 3.0
	 *
	 * @return SubobjectListFinder
	 */
	public function newSubobjectListFinder() {

		$subobjectListFinder = new SubobjectListFinder(
			$this->store,
			$this->applicationFactory->getIteratorFactory()
		);

		return $subobjectListFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return ChangePropListener
	 */
	public function newChangePropListener() {
		return new ChangePropListener();
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return ChangeOp
	 */
	public function newChangeOp( DIWikiPage $subject ) {
		return new ChangeOp( $subject );
	}

}
