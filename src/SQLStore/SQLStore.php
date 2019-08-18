<?php

namespace SMW\SQLStore;

use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\RequestOptions;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWQuery as Query;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableDefinition;
use Title;

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
 * @license GNU GPL v2+
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
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var PropertyTableInfoFetcher|null
	 */
	private $propertyTableInfoFetcher = null;

	/**
	 * @var PropertyTableIdReferenceFinder
	 */
	private $propertyTableIdReferenceFinder;

	/**
	 * @var dataItemHandlerFactory
	 */
	private $dataItemHandlerFactory;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var ServicesContainer
	 */
	protected $servicesContainer;

	/**
	 * Object to access the SMW IDs table.
	 *
	 * @since 1.8
	 * @var SMWSql3SmwIds
	 */
	public $smwIds;

	/**
	 * @var SQLStoreUpdater
	 */
	private $updater;

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
	public function setFactory( SQLStoreFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Get an object of the dataitem handler from the dataitem provided.
	 *
	 * @since 1.8
	 * @param integer $diType
	 *
	 * @return SMWDIHandler
	 * @throws RuntimeException if no handler exists for the given type
	 */
	public function getDataItemHandlerForDIType( $diType ) {

		if ( $this->dataItemHandlerFactory === null ) {
			$this->dataItemHandlerFactory = $this->factory->newDataItemHandlerFactory( $this );
		}

		return $this->dataItemHandlerFactory->getHandlerByType( $diType );
	}

///// Reading methods /////

	/**
	 * @see EntityLookup::getSemanticData
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {

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
	public function getPropertyValues( $subject, DIProperty $property, $requestOptions = null ) {

		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getPropertyValues(	$subject, $property, $requestOptions );
	}

	/**
	 * @see EntityLookup::getProperties
	 *
	 * {@inheritDoc}
	 */
	public function getProperties( DIWikiPage $subject, $requestOptions = null ) {

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
	public function getPropertySubjects( DIProperty $property, $dataItem, $requestOptions = null ) {

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
	public function getAllPropertySubjects( DIProperty $property, $requestoptions = null ) {

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
	public function getInProperties( DataItem $value, $requestoptions = null ) {

		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup->getInProperties( $value, $requestoptions );
	}

///// Writing methods /////


	public function deleteSubject( Title $title ) {

		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		$subject = DIWikiPage::newFromTitle( $title );

		$this->updater->deleteSubject( $title );

		$this->doDeferredCachedListLookupUpdate(
			$subject
		);
	}

	protected function doDataUpdate( SemanticData $semanticData ) {

		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		$this->updater->doDataUpdate( $semanticData );

		$this->doDeferredCachedListLookupUpdate(
			$semanticData->getSubject()
		);
	}

	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {

		if ( $this->updater === null ) {
			$this->updater = $this->factory->newUpdater();
		}

		\Hooks::run(
			'SMW::SQLStore::BeforeChangeTitleComplete',
			[ $this, $oldTitle, $newTitle, $pageId, $redirectId ]
		);

		$this->updater->changeTitle( $oldTitle, $newTitle, $pageId, $redirectId );

		$this->doDeferredCachedListLookupUpdate(
			DIWikiPage::newFromTitle( $oldTitle )
		);
	}

	private function doDeferredCachedListLookupUpdate( DIWikiPage $subject ) {

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return null;
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
	 * @param Query $query
	 * @return QueryResult|string|integer depends on $query->querymode
	 */
	public function getQueryResult( Query $query ) {

		$result = null;
		$start = microtime( true );

		if ( \Hooks::run( 'SMW::Store::BeforeQueryResultLookupComplete', [ $this, $query, &$result, $this->factory->newSlaveQueryEngine() ] ) ) {
			$result = $this->fetchQueryResult( $query );
		}

		\Hooks::run( 'SMW::SQLStore::AfterQueryResultLookupComplete', [ $this, &$result ] );
		\Hooks::run( 'SMW::Store::AfterQueryResultLookupComplete', [ $this, &$result ] );

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
	public function getPropertiesSpecial( $requestOptions = null ) {
		return $this->factory->newPropertyUsageCachedListLookup( $requestOptions );
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function getUnusedPropertiesSpecial( $requestOptions = null ) {
		return $this->factory->newUnusedPropertyCachedListLookup( $requestOptions );
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function getWantedPropertiesSpecial( $requestOptions = null ) {
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
	public function drop( $verbose = true ) {

		$installer = $this->factory->newInstaller();
		$installer->setMessageReporter( $this->messageReporter );

		return $installer->uninstall( $verbose );
	}

	public function refreshData( &$id, $count, $namespaces = false, $usejobs = true ) {

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
	 * @param Title $concept
	 * @return array of error strings (empty if no errors occurred)
	 */
	public function refreshConceptCache( Title $concept ) {
		return $this->factory->newMasterConceptCache()->refreshConceptCache( $concept );
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @since 1.8
	 * @param Title $concept
	 */
	public function deleteConceptCache( $concept ) {
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
	 * @param Title|SMWWikiPageValue $concept
	 *
	 * @return DIConcept|null
	 */
	public function getConceptCacheStatus( $concept ) {
		return $this->factory->newSlaveConceptCache()->getStatus( $concept );
	}


///// Helper methods, mostly protected /////

	/**
	 * @see RequestOptionsProcessor::getSQLOptions
	 *
	 * @since 1.8
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $valuecol
	 *
	 * @return array
	 */
	public function getSQLOptions( RequestOptions $requestOptions = null, $valueCol = '' ) {
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
	 * @param boolean $addAnd indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public function getSQLConditions( RequestOptions $requestOptions = null, $valueCol = '', $labelCol = '', $addAnd = true ) {
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
	public function applyRequestOptions( array $data, RequestOptions $requestOptions = null ) {
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
	 * @param integer $dataItemId
	 *
	 * @return string
	 */
	public function findDiTypeTableId( $dataItemId ) {
		return $this->getPropertyTableInfoFetcher()->findTableIdForDataItemTypeId( $dataItemId );
	}

	/**
	 * PropertyTableInfoFetcher::findTableIdForProperty
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public function findPropertyTableID( DIProperty $property ) {
		return $this->getPropertyTableInfoFetcher()->findTableIdForProperty( $property );
	}

	/**
	 * PropertyTableInfoFetcher::getPropertyTableDefinitions
	 *
	 * @return TableDefinition[]
	 */
	public function getPropertyTables() {
		return $this->getPropertyTableInfoFetcher()->getPropertyTableDefinitions();
	}

	/**
	 * Returns SMW Id object
	 *
	 * @since 1.9
	 *
	 * @return SMWSql3SmwIds
	 */
	public function getObjectIds() {
		return $this->smwIds;
	}

	/**
	 * Resets internal objects
	 *
	 * @since 1.9.1.1
	 */
	public function clear() {
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
	 * @return array
	 */
	public function getInfo( $type = null ) {

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
	public function getPropertyTableInfoFetcher() {

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
	public function getPropertyTableIdReferenceFinder() {

		if ( $this->propertyTableIdReferenceFinder === null ) {
			$this->propertyTableIdReferenceFinder = $this->factory->newPropertyTableIdReferenceFinder();
		}

		return $this->propertyTableIdReferenceFinder;
	}

	/**
	 * @return ServicesContainer
	 */
	protected function newServicesContainer() {
		return $this->factory->newServicesContainer();
	}

}
