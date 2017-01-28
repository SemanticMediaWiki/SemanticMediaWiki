<?php

use SMW\DataTypeRegistry;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\TableDefinition;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * SQL-based implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 *
 * @ingroup SMWStore
 */

// The use of the following constants is explained in SMWSQLStore3::setup():
define( 'SMW_SQL3_SMWIW_OUTDATED', ':smw' ); // virtual "interwiki prefix" for old-style special SMW objects (no longer used)
define( 'SMW_SQL3_SMWREDIIW', ':smw-redi' ); // virtual "interwiki prefix" for SMW objects that are redirected
define( 'SMW_SQL3_SMWBORDERIW', ':smw-border' ); // virtual "interwiki prefix" separating very important pre-defined properties from the rest
define( 'SMW_SQL3_SMWINTDEFIW', ':smw-intprop' ); // virtual "interwiki prefix" marking internal (invisible) predefined properties
define( 'SMW_SQL3_SMWDELETEIW', ':smw-delete' ); // virtual "interwiki prefix" marking a deleted subject, see #1100

/**
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
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3 extends SMWStore {

	/**
	 * Specifies the border limit (upper bound) for pre-defined properties used
	 * in the ID_TABLE
	 *
	 * When changing the upper bound, please make sure to copy the current upper
	 * bound as legcy to the SMWSQLStore3SetupHandlers::checkPredefinedPropertyBorder
	 */
	const FIXED_PROPERTY_ID_UPPERBOUND = 50;

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
	 * @var RequestOptionsProcessor|null
	 */
	private $requestOptionsProcessor = null;

	/**
	 * @var DataItemHandlerDispatcher
	 */
	private $dataItemHandlerDispatcher;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * Object to access the SMW IDs table.
	 *
	 * @since 1.8
	 * @var SMWSql3SmwIds
	 */
	public $smwIds;

	/**
	 * The reader object used by this store. Initialized by getReader()
	 * Always access using getReader()
	 *
	 * @since 1.8
	 * @var SMWSQLStore3Readers
	 */
	protected $reader = false;

	/**
	 * The writer object used by this store. Initialized by getWriter(),
	 * which is the only way in which it should be accessed.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3Writers
	 */
	protected $writer = false;

	/**
	 * Cache for SemanticData objects, indexed by SMW ID.
	 *
	 * @todo In the future, the cache should be managed by a helper class.
	 *
	 * @since 1.8
	 * @var array
	 */
	public $m_semdata = array();

	/**
	 * Like SMWSQLStore3::m_semdata, but containing flags indicating
	 * completeness of the SemanticData objs.
	 *
	 * @since 1.8
	 * @var array
	 */
	public $m_sdstate = array();

	/**
	 * @since 1.8
	 */
	public function __construct() {
		$this->factory = new SQLStoreFactory( $this );
		$this->smwIds = $this->factory->newIdTableManager();
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

		if ( $this->dataItemHandlerDispatcher === null ) {
			$this->dataItemHandlerDispatcher = $this->factory->newDataItemHandlerDispatcher( $this );
		}

		return $this->dataItemHandlerDispatcher->getHandlerByType( $diType );
	}

///// Reading methods /////

	public function getReader() {
		if( $this->reader == false ) {
			$this->reader = new SMWSQLStore3Readers( $this, $this->factory );//Initialize if not done already
		}

		return $this->reader;
	}

	public function getSemanticData( DIWikiPage $subject, $filter = false ) {
		return $this->getEntityLookup()->getSemanticData( $subject, $filter );
	}

	/**
	 * @param mixed $subject
	 * @param DIProperty $property
	 * @param null $requestOptions
	 *
	 * @return SMWDataItem[]
	 */
	public function getPropertyValues( $subject, DIProperty $property, $requestOptions = null ) {
		return $this->getEntityLookup()->getPropertyValues(	$subject, $property, $requestOptions );
	}

	public function getProperties( DIWikiPage $subject, $requestOptions = null ) {
		return $this->getEntityLookup()->getProperties( $subject, $requestOptions );
	}

	public function getPropertySubjects( DIProperty $property, $dataItem, $requestOptions = null ) {
		return $this->getEntityLookup()->getPropertySubjects( $property, $dataItem, $requestOptions );
	}

	public function getAllPropertySubjects( DIProperty $property, $requestoptions = null ) {
		return $this->getEntityLookup()->getAllPropertySubjects( $property, $requestoptions );
	}

	public function getInProperties( SMWDataItem $value, $requestoptions = null ) {
		return $this->getEntityLookup()->getInProperties( $value, $requestoptions );
	}


///// Writing methods /////


	public function getWriter() {
		if( $this->writer == false ) {
			$this->writer = new SMWSQLStore3Writers( $this, $this->factory );
		}

		return $this->writer;
	}

	public function deleteSubject( Title $title ) {

		$subject = DIWikiPage::newFromTitle( $title );

		$this->getEntityLookup()->resetCacheBy(
			$subject
		);

		$this->getWriter()->deleteSubject( $title );

		$this->doDeferredCachedListLookupUpdate(
			$subject
		);
	}

	protected function doDataUpdate( SemanticData $semanticData ) {

		$this->getEntityLookup()->resetCacheBy(
			$semanticData->getSubject()
		);

		$this->getWriter()->doDataUpdate( $semanticData );

		$this->doDeferredCachedListLookupUpdate(
			$semanticData->getSubject()
		);
	}

	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {

		$this->getEntityLookup()->resetCacheBy(
			DIWikiPage::newFromTitle( $oldtitle )
		);

		$this->getEntityLookup()->resetCacheBy(
			DIWikiPage::newFromTitle( $newtitle )
		);

		$this->getWriter()->changeTitle( $oldtitle, $newtitle, $pageid, $redirid );

		$this->doDeferredCachedListLookupUpdate(
			DIWikiPage::newFromTitle( $oldtitle )
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
	 * @param SMWQuery $query
	 * @return SMWQueryResult|string|integer depends on $query->querymode
	 */
	public function getQueryResult( SMWQuery $query ) {

		$result = null;
		$start = microtime( true );

		if ( \Hooks::run( 'SMW::Store::BeforeQueryResultLookupComplete', array( $this, $query, &$result, $this->factory->newSlaveQueryEngine() ) ) ) {
			$result = $this->fetchQueryResult( $query );
		}

		\Hooks::run( 'SMW::SQLStore::AfterQueryResultLookupComplete', array( $this, &$result ) );
		\Hooks::run( 'SMW::Store::AfterQueryResultLookupComplete', array( $this, &$result ) );

		$query->setOption( SMWQuery::PROC_QUERY_TIME, microtime( true ) - $start );

		return $result;
	}

	protected function fetchQueryResult( SMWQuery $query ) {
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
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function setup( $verbose = true ) {
		return $this->factory->newInstaller()->install( $verbose );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function drop( $verbose = true ) {
		return $this->factory->newInstaller()->uninstall( $verbose );
	}

	public function refreshData( &$id, $count, $namespaces = false, $usejobs = true ) {

		$entityRebuildDispatcher = $this->factory->newEntityRebuildDispatcher();

		$entityRebuildDispatcher->setDispatchRangeLimit( $count );
		$entityRebuildDispatcher->setRestrictionToNamespaces( $namespaces );
		$entityRebuildDispatcher->useJobQueueScheduler( $usejobs );

		return $entityRebuildDispatcher;
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
	 * cachable). If status is not 'no', the array also contains keys 'size'
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
	 * @see RequestOptionsProcessor::getSQLOptionsFrom
	 *
	 * @since 1.8
	 *
	 * @param SMWRequestOptions|null $requestOptions
	 * @param string $valuecol
	 *
	 * @return array
	 */
	public function getSQLOptions( SMWRequestOptions $requestOptions = null, $valueCol = '' ) {
		return $this->getRequestOptionsProcessor()->getSQLOptionsFrom( $requestOptions, $valueCol );
	}

	/**
	 * @see RequestOptionsProcessor::getSQLConditionsFrom
	 *
	 * @since 1.8
	 *
	 * @param SMWRequestOptions|null $requestOptions
	 * @param string $valueCol name of SQL column to which conditions apply
	 * @param string $labelCol name of SQL column to which string conditions apply, if any
	 * @param boolean $addAnd indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public function getSQLConditions( SMWRequestOptions $requestOptions = null, $valueCol = '', $labelCol = '', $addAnd = true ) {
		return $this->getRequestOptionsProcessor()->getSQLConditionsFrom( $requestOptions, $valueCol, $labelCol, $addAnd );
	}

	/**
	 * @see RequestOptionsProcessor::applyRequestOptionsTo
	 *
	 * @since 1.8
	 *
	 * @param array $data array of SMWDataItem objects
	 * @param SMWRequestOptions|null $requestOptions
	 *
	 * @return SMWDataItem[]
	 */
	public function applyRequestOptions( array $data, SMWRequestOptions $requestOptions = null ) {
		return $this->getRequestOptionsProcessor()->applyRequestOptionsTo( $data, $requestOptions );
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
	 * Change an SMW page id across all relevant tables. The redirect table
	 * is also updated (without much effect if the change happended due to
	 * some redirect, since the table should not contain the id of the
	 * redirected page). If namespaces are given, then they are used to
	 * delete any entries that are limited to one particular namespace (e.g.
	 * only properties can be used as properties) instead of moving them.
	 *
	 * The id in the SMW IDs table is not touched.
	 *
	 * @note This method only changes internal page IDs in SMW. It does not
	 * assume any change in (title-related) data, as e.g. in a page move.
	 * Internal objects (subobject) do not need to be updated since they
	 * refer to the title of their parent page, not to its ID.
	 *
	 * @since 1.8
	 * @param integer $oldid numeric ID that is to be changed
	 * @param integer $newid numeric ID to which the records are to be changed
	 * @param integer $oldnamespace namespace of old id's page (-1 to ignore it)
	 * @param integer $newnamespace namespace of new id's page (-1 to ignore it)
	 * @param boolean $sdata stating whether to update subject references
	 * @param boolean $podata stating if to update property/object references
	 */
	public function changeSMWPageID( $oldid, $newid, $oldnamespace = -1,
				$newnamespace = -1, $sdata = true, $podata = true ) {

		$db = $this->getConnection( 'mw.db' );

		// Change all id entries in property tables:
		foreach ( $this->getPropertyTables() as $proptable ) {
			if ( $sdata && $proptable->usesIdSubject() ) {
				$db->update( $proptable->getName(), array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			}

			if ( $podata ) {
				if ( ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_PROPERTY ) ) && ( !$proptable->isFixedPropertyTable() ) ) {
					if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_PROPERTY ) ) {
						$db->update( $proptable->getName(), array( 'p_id' => $newid ), array( 'p_id' => $oldid ), __METHOD__ );
					} else {
						$db->delete( $proptable->getName(), array( 'p_id' => $oldid ), __METHOD__ );
					}
				}

				foreach ( $proptable->getFields( $this ) as $fieldName => $fieldType ) {
					if ( $fieldType === FieldType::FIELD_ID ) {
						$db->update( $proptable->getName(), array( $fieldName => $newid ), array( $fieldName => $oldid ), __METHOD__ );
					}
				}
			}
		}

		// Change id entries in concept-related tables:
		if ( $sdata && ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_CONCEPT ) ) ) {
			if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_CONCEPT ) ) {
				$db->update( 'smw_fpt_conc', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
				$db->update( self::CONCEPT_CACHE_TABLE, array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			} else {
				$db->delete( 'smw_fpt_conc', array( 's_id' => $oldid ), __METHOD__ );
				$db->delete( self::CONCEPT_CACHE_TABLE, array( 's_id' => $oldid ), __METHOD__ );
			}
		}

		if ( $podata ) {
			$db->update( self::CONCEPT_CACHE_TABLE, array( 'o_id' => $newid ), array( 'o_id' => $oldid ), __METHOD__ );
		}
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
	 * Returns the statics table
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getStatisticsTable() {
		return self::PROPERTY_STATISTICS_TABLE;
	}

	/**
	 * Resets internal objects
	 *
	 * @since 1.9.1.1
	 */
	public function clear() {
		parent::clear();
		$this->m_semdata = array();
		$this->m_sdstate = array();
		$this->propertyTableInfoFetcher = null;
		$this->getObjectIds()->clearCaches();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $connectionTypeId
	 *
	 * @return \SMW\MediaWiki\Database
	 */
	public function getConnection( $connectionTypeId = 'mw.db' ) {
		return parent::getConnection( $connectionTypeId );
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
	 * @return RequestOptionsProcessor
	 */
	private function getRequestOptionsProcessor() {

		if ( $this->requestOptionsProcessor === null ) {
			$this->requestOptionsProcessor = $this->factory->newRequestOptionsProcessor();
		}

		return $this->requestOptionsProcessor;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {

		if ( $this->entityLookup === null ) {
			$this->entityLookup = $this->factory->newEntityLookup();
		}

		return $this->entityLookup;
	}

}
