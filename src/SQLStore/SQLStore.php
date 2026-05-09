<?php

namespace SMW\SQLStore;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use RuntimeException;
use SMW\DataItems\Concept;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValues\WikiPageValue;
use SMW\Lookup\CachedListLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\Query\Query;
use SMW\RequestOptions;
use SMW\Services\ServicesContainer;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\EntityLookup;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\Store;

/*
 * Virtual "interwiki prefix" for old-style special SMW objects (no longer used)
 */
define( 'SMW_SQL3_SMWIW_OUTDATED', ':smw' );

/*
 * Virtual "interwiki prefix" for SMW objects that are redirected
 */
define( 'SMW_SQL3_SMWREDIIW', ':smw-redi' );

/*
 * Virtual "interwiki prefix" separating very important pre-defined properties
 * from the rest
 */
define( 'SMW_SQL3_SMWBORDERIW', ':smw-border' );

/*
 * Virtual "interwiki prefix" marking internal (invisible) predefined properties
 */
define( 'SMW_SQL3_SMWINTDEFIW', ':smw-intprop' );

/*
 * Virtual "interwiki prefix" marking a deleted subject, see #1100
 */
define( 'SMW_SQL3_SMWDELETEIW', ':smw-delete' );

/**
 * SQL-based implementation of SMW's storage abstraction layer.
 *
 * Storage access class for using the standard MediaWiki SQL database for
 * keeping semantic data.
 *
 * @note Regarding the use of interwiki links in the store, there is currently
 * no support for storing semantic data about interwiki objects, and hence
 * queries that involve interwiki objects really make sense only for them
 * occurring in object positions. Most methods still use the given input
 * interwiki text as a simple way to filter out results that may be found if an
 * interwiki object is given but a local object of the same name exists. It is
 * currently not planned to support things like interwiki reuse of properties.
 *
 * @license GPL-2.0-or-later
 * @since 1.8
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 * @author mwjames
 */
class SQLStore extends Store {

	/**
	 * Specifies the border limit (upper bound) for pre-defined properties used
	 * in the ID_TABLE
	 *
	 * When changing the upper bound, please make sure to copy the current upper
	 * bound as legacy to the TableIntegrityExaminer::checkOnPostCreation
	 */
	const FIXED_PROPERTY_ID_UPPERBOUND = 500;

	/**
	 * Name of the table to store the concept cache in.
	 *
	 * @note This should never change. If it is changed, the concept caches
	 * will appear empty until they are recomputed.
	 */
	const CONCEPT_CACHE_TABLE = 'smw_concept_cache';
	const CONCEPT_TABLE = 'smw_fpt_conc';

	/**
	 * Name of the table to store the concept cache in.
	 *
	 * @note This should never change, but if it does then its contents can
	 * simply be rebuilt by running the setup.
	 */
	const PROPERTY_STATISTICS_TABLE = 'smw_prop_stats';

	/**
	 * Name of the table that manages the query dependency links
	 */
	const QUERY_LINKS_TABLE = 'smw_query_links';

	/**
	 * Name of the table that manages the fulltext index
	 */
	const FT_SEARCH_TABLE = 'smw_ft_search';

	/**
	 * Name of the table that manages the Store IDs
	 */
	const ID_TABLE = 'smw_object_ids';

	/**
	 * Name of the ID auxiliary table
	 */
	const ID_AUXILIARY_TABLE = 'smw_object_aux';

	/**
	 * Identifies the UPDATE transaction
	 */
	const UPDATE_TRANSACTION = 'sql/transaction/update';

	private SQLStoreFactory $factory;

	private ?PropertyTableInfoFetcher $propertyTableInfoFetcher = null;

	private ?PropertyTableIdReferenceFinder $propertyTableIdReferenceFinder = null;

	private ?DataItemHandlerFactory $dataItemHandlerFactory = null;

	private ?EntityLookup $entityLookup = null;

	/**
	 * @var ?ServicesContainer
	 */
	protected $servicesContainer;

	/**
	 * Object to access the SMW IDs table.
	 *
	 * @since 1.8
	 * @var EntityIdManager
	 */
	public $smwIds;

	private ?SQLStoreUpdater $updater = null;

	/**
	 * @since 1.8
	 */
	public function __construct() {
		$this->factory = new SQLStoreFactory( $this, $this->messageReporter );
		$this->smwIds = $this->factory->newEntityIdManager();
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStoreFactory $factory
	 */
	public function setFactory( SQLStoreFactory $factory ): void {
		$this->factory = $factory;
	}

	/**
	 * Get an object of the dataitem handler from the dataitem provided.
	 *
	 * @since 1.8
	 *
	 * @param int $diType
	 *
	 * @return DataItemHandler
	 * @throws RuntimeException if no handler exists for the given type
	 */
	public function getDataItemHandlerForDIType( $diType ) {
		if ( $this->dataItemHandlerFactory === null ) {
			$this->dataItemHandlerFactory = $this->factory->newDataItemHandlerFactory();
		}

		return $this->dataItemHandlerFactory->getHandlerByType( $diType );
	}

///// Reading methods /////

	/**
	 * @see EntityLookup::getSemanticData
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData( WikiPage $subject, $filter = false ) {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getSemanticData( $subject, $filter );
	}

	/**
	 * @see EntityLookup::getPropertyValues
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyValues( $subject, Property $property, $requestOptions = null ) {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getPropertyValues( $subject, $property, $requestOptions );
	}

	/**
	 * @see EntityLookup::getProperties
	 *
	 * {@inheritDoc}
	 */
	public function getProperties( WikiPage $subject, $requestOptions = null ): array {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getProperties( $subject, $requestOptions );
	}

	/**
	 * @see EntityLookup::getPropertySubjects
	 *
	 * {@inheritDoc}
	 */
	public function getPropertySubjects( Property $property, $dataItem, $requestOptions = null ) {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getPropertySubjects( $property, $dataItem, $requestOptions );
	}

	/**
	 * @see EntityLookup::getAllPropertySubjects
	 *
	 * {@inheritDoc}
	 */
	public function getAllPropertySubjects( Property $property, $requestoptions = null ) {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getAllPropertySubjects( $property, $requestoptions );
	}

	/**
	 * @see EntityLookup::getInProperties
	 *
	 * {@inheritDoc}
	 */
	public function getInProperties( DataItem $value, $requestoptions = null ): array {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getInProperties( $value, $requestoptions );
	}

	public function deleteSubject( Title $title ) {
		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		$subject = WikiPage::newFromTitle( $title );

		$status = $this->updater->deleteSubject( $title );

		$this->doDeferredCachedListLookupUpdate(
			$subject
		);

		return $status;
	}

	protected function doDataUpdate( SemanticData $semanticData ) {
		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		$status = $this->updater->doDataUpdate( $semanticData );

		$this->doDeferredCachedListLookupUpdate(
			$semanticData->getSubject()
		);

		return $status;
	}

	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {
		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		MediaWikiServices::getInstance()
			->getHookContainer()
			->run(
				'SMW::SQLStore::BeforeChangeTitleComplete',
				[ $this, $oldTitle, $newTitle, $pageId, $redirectId ]
			);

		$status = $this->updater->changeTitle( $oldTitle, $newTitle, $pageId, $redirectId );

		$this->doDeferredCachedListLookupUpdate(
			WikiPage::newFromTitle( $oldTitle )
		);

		return $status;
	}

	private function doDeferredCachedListLookupUpdate( WikiPage $subject ): void {
		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$deferredCallableUpdate = $this->factory->newDeferredCallableCachedListLookupUpdate();
		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->waitOnTransactionIdle();
		$deferredCallableUpdate->pushUpdate();
	}

///// Query answering /////

	/**
	 * @note Move hooks to the base class in 3.*
	 *
	 * @see SMWStore::fetchQueryResult
	 *
	 * @since 1.8
	 *
	 * @param Query $query
	 *
	 * @return mixed depends on $query->querymode
	 */
	public function getQueryResult( Query $query ) {
		$result = null;
		$start = microtime( true );

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		if ( $hookContainer->run( 'SMW::Store::BeforeQueryResultLookupComplete', [ $this, $query, &$result, $this->factory->newSlaveQueryEngine() ] ) ) {
			$result = $this->fetchQueryResult( $query );
		}

		$hookContainer->run( 'SMW::SQLStore::AfterQueryResultLookupComplete', [ $this, &$result ] );
		$hookContainer->run( 'SMW::Store::AfterQueryResultLookupComplete', [ $this, &$result ] );

		$query->setOption( Query::PROC_QUERY_TIME, microtime( true ) - $start );

		return $result;
	}

	protected function fetchQueryResult( Query $query ) {
		return $this->factory->newSlaveQueryEngine()->getQueryResult( $query );
	}

///// Special page functions /////

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function getPropertiesSpecial( $requestOptions = null ): CachedListLookup {
		return $this->factory->newPropertyUsageCachedListLookup( $requestOptions );
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function getUnusedPropertiesSpecial( $requestOptions = null ): CachedListLookup {
		return $this->factory->newUnusedPropertyCachedListLookup( $requestOptions );
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function getWantedPropertiesSpecial( $requestOptions = null ): CachedListLookup {
		return $this->factory->newUndeclaredPropertyCachedListLookup( $requestOptions );
	}

	public function getStatistics() {
		return $this->factory->newUsageStatisticsCachedListLookup()->fetchList();
	}

///// Setup store /////

	/**
	 * @see Store::service
	 *
	 * {@inheritDoc}
	 */
	public function service( $service, ...$args ) {
		if ( $this->servicesContainer === null ) {
			$this->servicesContainer = $this->newServicesContainer();
		}

		return $this->servicesContainer->get( $service, ...$args );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function setup( $verbose = true ) {
		$installer = $this->factory->newInstaller();
		$installer->setMessageReporter( $this->messageReporter );

		return $installer->install( $verbose );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function drop( $verbose = true ): bool {
		$installer = $this->factory->newInstaller();
		$installer->setMessageReporter( $this->messageReporter );

		return $installer->uninstall( $verbose );
	}

	public function refreshData( &$id, $count, $namespaces = false, $usejobs = true ): Rebuilder {
		$rebuilder = $this->factory->newRebuilder();

		$rebuilder->setDispatchRangeLimit( $count );
		$rebuilder->setRestrictionToNamespaces( $namespaces );

		$rebuilder->setOptions(
			[
				'use-job' => $usejobs
			]
		);

		return $rebuilder;
	}

///// Concept caching /////

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @since 1.8
	 *
	 * @param Title $concept
	 *
	 * @return array of error strings (empty if no errors occurred)
	 */
	public function refreshConceptCache( Title $concept ): array {
		return $this->factory->newMasterConceptCache()->refreshConceptCache( $concept );
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @since 1.8
	 *
	 * @param Title $concept
	 */
	public function deleteConceptCache( Title $concept ): void {
		$this->factory->newMasterConceptCache()->deleteConceptCache( $concept );
	}

	/**
	 * Return status of the concept cache for the given concept as an array
	 * with key 'status' ('empty': not cached, 'full': cached, 'no': not
	 * cacheable). If status is not 'no', the array also contains keys 'size'
	 * (query size), 'depth' (query depth), 'features' (query features). If
	 * status is 'full', the array also contains keys 'date' (timestamp of
	 * cache), 'count' (number of results in cache).
	 *
	 * @since 1.8
	 *
	 * @param Title|WikiPageValue $concept
	 *
	 * @return Concept|null
	 */
	public function getConceptCacheStatus( $concept ): ?Concept {
		return $this->factory->newSlaveConceptCache()->getStatus( $concept );
	}

///// Helper methods, mostly protected /////

	/**
	 * @see RequestOptionsProcessor::getSQLOptions
	 *
	 * @since 1.8
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $valueCol
	 *
	 * @return array
	 */
	public function getSQLOptions( ?RequestOptions $requestOptions = null, $valueCol = '' ): array {
		return RequestOptionsProcessor::getSQLOptions( $requestOptions, $valueCol );
	}

	/**
	 * @see RequestOptionsProcessor::getSQLConditions
	 *
	 * @since 1.8
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $valueCol name of SQL column to which conditions apply
	 * @param string $labelCol name of SQL column to which string conditions apply, if any
	 * @param bool $addAnd indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public function getSQLConditions( ?RequestOptions $requestOptions = null, $valueCol = '', $labelCol = '', $addAnd = true ): string {
		return RequestOptionsProcessor::getSQLConditions( $this, $requestOptions, $valueCol, $labelCol, $addAnd );
	}

	/**
	 * @see RequestOptionsProcessor::applyRequestOptions
	 *
	 * @since 1.8
	 *
	 * @param array $data array of DataItem objects
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DataItem[]
	 */
	public function applyRequestOptions( array $data, ?RequestOptions $requestOptions = null ): array {
		return RequestOptionsProcessor::applyRequestOptions( $this, $data, $requestOptions );
	}

	/**
	 * PropertyTableInfoFetcher::findTableIdForDataTypeTypeId
	 *
	 * @param string $typeid
	 *
	 * @return string
	 */
	public function findTypeTableId( $typeid ) {
		return $this->getPropertyTableInfoFetcher()->findTableIdForDataTypeTypeId( $typeid );
	}

	/**
	 * PropertyTableInfoFetcher::findTableIdForDataItemTypeId
	 *
	 * @param int $dataItemId
	 *
	 * @return string
	 */
	public function findDiTypeTableId( $dataItemId ) {
		return $this->getPropertyTableInfoFetcher()->findTableIdForDataItemTypeId( $dataItemId );
	}

	/**
	 * PropertyTableInfoFetcher::findTableIdForProperty
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	public function findPropertyTableID( Property $property ) {
		return $this->getPropertyTableInfoFetcher()->findTableIdForProperty( $property );
	}

	/**
	 * PropertyTableInfoFetcher::getPropertyTableDefinitions
	 *
	 * @return PropertyTableDefinition[]
	 */
	public function getPropertyTables() {
		return $this->getPropertyTableInfoFetcher()->getPropertyTableDefinitions();
	}

	/**
	 * Returns SMW Id object
	 *
	 * @since 1.9
	 *
	 * @return EntityIdManager
	 */
	public function getObjectIds() {
		return $this->smwIds;
	}

	/**
	 * Resets internal objects
	 *
	 * @since 1.9.1.1
	 */
	public function clear(): void {
		parent::clear();
		$this->factory->newSemanticDataLookup()->clear();
		$this->propertyTableInfoFetcher = null;
		$this->servicesContainer = null;
		$this->getObjectIds()->initCache();
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $type
	 *
	 * @return array|string
	 */
	public function getInfo( $type = null ): string|array {
		if ( $type === 'store' ) {
			return 'SMWSQLStore';
		}

		$connection = $this->getConnection( 'mw.db' );

		if ( $type === 'db' ) {
			return $connection->getInfo();
		}

		return [
			'SMWSQLStore' => $connection->getInfo()
		];
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 *
	 * @return Database
	 */
	public function getConnection( $type = 'mw.db' ) {
		return parent::getConnection( $type );
	}

	/**
	 * @since 2.2
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function getPropertyTableInfoFetcher(): PropertyTableInfoFetcher {
		if ( $this->propertyTableInfoFetcher === null ) {
			$this->propertyTableInfoFetcher = $this->factory->newPropertyTableInfoFetcher();
		}

		return $this->propertyTableInfoFetcher;
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertyTableIdReferenceFinder
	 */
	public function getPropertyTableIdReferenceFinder(): PropertyTableIdReferenceFinder {
		if ( $this->propertyTableIdReferenceFinder === null ) {
			$this->propertyTableIdReferenceFinder = $this->factory->newPropertyTableIdReferenceFinder();
		}

		return $this->propertyTableIdReferenceFinder;
	}

	/**
	 * @return ServicesContainer
	 */
	protected function newServicesContainer(): ServicesContainer {
		return $this->factory->newServicesContainer();
	}

}
