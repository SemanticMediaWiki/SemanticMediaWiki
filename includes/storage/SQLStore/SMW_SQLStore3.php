<?php

use SMW\DIConcept;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\QueryEngine\ConceptCache;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\WantedPropertiesCollector;
use SMW\SQLStore\UnusedPropertiesCollector;
use SMW\SQLStore\PropertiesCollector;
use SMW\SQLStore\StatisticsCollector;
use SMW\DataTypeRegistry;
use SMW\Settings;
use SMW\SQLStore\TableDefinition;
use SMW\SQLStore\ListLookup\ListLookupCache;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;

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
	 * Name of the table to store the concept cache in.
	 *
	 * @note This should never change. If it is changed, the concept caches
	 * will appear empty until they are recomputed.
	 */
	const CONCEPT_CACHE_TABLE = 'smw_concept_cache';

	/**
	 * Name of the table to store the concept cache in.
	 *
	 * @note This should never change, but if it does then its contents can
	 * simply be rebuilt by running the setup.
	 */
	const PROPERTY_STATISTICS_TABLE = 'smw_prop_stats';

	/**
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var PropertyTableInfoFetcher|null
	 */
	private $propertyTableInfoFetcher = null;

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
	 * The SetupHandler object used by this store. Initialized by getSetupHandler(),
	 * which is the only way in which it should be accessed.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3SetupHandlers
	 */
	protected $setupHandler = false;

	/**
	 * Array of DIHandler objects used by this store. Initialized by getDIHandler(),
	 * which is the only way in which it should be accessed.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected $diHandlers = array();

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
		$this->smwIds = new SMWSql3SmwIds( $this );
		$this->factory = new SQLStoreFactory( $this );
	}

	/**
	 * Get an object of the dataitem handler from the dataitem provided.
	 *
	 * @since 1.8
	 * @param integer $diType
	 * @throws MWException if no handler exists for the given type
	 * @return SMWDataItemHandler
	 */
	public function getDataItemHandlerForDIType( $diType ) {
		if( !array_key_exists( $diType, $this->diHandlers ) ) {
			switch ( $diType ) {
				case SMWDataItem::TYPE_NUMBER:
					$this->diHandlers[$diType] = new SMWDIHandlerNumber( $this );
					break;
				case SMWDataItem::TYPE_BLOB:
					$this->diHandlers[$diType] = new SMWDIHandlerBlob( $this );
					break;
				case SMWDataItem::TYPE_BOOLEAN:
					$this->diHandlers[$diType] = new SMWDIHandlerBoolean( $this );
					break;
				case SMWDataItem::TYPE_URI:
					$this->diHandlers[$diType] = new SMWDIHandlerUri( $this );
					break;
				case SMWDataItem::TYPE_TIME:
					$this->diHandlers[$diType] = new SMWDIHandlerTime( $this );
					break;
				case SMWDataItem::TYPE_GEO:
					$this->diHandlers[$diType] = new SMWDIHandlerGeoCoord( $this );
					break;
				case SMWDataItem::TYPE_WIKIPAGE:
					$this->diHandlers[$diType] = new SMWDIHandlerWikiPage( $this );
					break;
				case SMWDataItem::TYPE_CONCEPT:
					$this->diHandlers[$diType] = new SMWDIHandlerConcept( $this );
					break;
				case SMWDataItem::TYPE_PROPERTY:
					throw new MWException( "There is no DI handler for SMWDataItem::TYPE_PROPERTY." );
				case SMWDataItem::TYPE_CONTAINER:
					throw new MWException( "There is no DI handler for SMWDataItem::TYPE_CONTAINER." );
				case SMWDataItem::TYPE_ERROR:
					throw new MWException( "There is no DI handler for SMWDataItem::TYPE_ERROR." );
				default:
					throw new MWException( "The value \"$diType\" is not a valid dataitem ID." );
			}
		}
		return $this->diHandlers[$diType];
	}

	/**
	 * Convenience method to get a dataitem handler for a datatype id.
	 *
	 * @since 1.8
	 * @param string $typeid
	 * @throws MWException if there is no handler for this type
	 * @return SMWDataItemHandler
	 */
	public function getDataItemHandlerForDatatype( $typeid ) {
		$dataItemId = DataTypeRegistry::getInstance()->getDataItemId( $typeid );
		return $this->getDataItemHandlerForDIType( $dataItemId );
	}

///// Reading methods /////

	public function getReader() {
		if( $this->reader == false ) {
			$this->reader = new SMWSQLStore3Readers( $this );//Initialize if not done already
		}

		return $this->reader;
	}

	public function getSemanticData( DIWikiPage $subject, $filter = false ) {
		return $this->getReader()->getSemanticData( $subject, $filter );
	}

	/**
	 * @param mixed $subject
	 * @param DIProperty $property
	 * @param null $requestoptions
	 *
	 * @return SMWDataItem[]
	 */
	public function getPropertyValues( $subject, DIProperty $property, $requestoptions = null ) {
		return $this->getReader()->getPropertyValues( $subject, $property, $requestoptions );
	}

	public function getPropertySubjects( DIProperty $property, $value, $requestoptions = null ) {
		return $this->getReader()->getPropertySubjects( $property, $value, $requestoptions );
	}

	public function getAllPropertySubjects( DIProperty $property, $requestoptions = null ) {
		return $this->getReader()->getAllPropertySubjects( $property, $requestoptions );
	}

	public function getProperties( DIWikiPage $subject, $requestoptions = null ) {
		return $this->getReader()->getProperties( $subject, $requestoptions );
	}

	public function getInProperties( SMWDataItem $value, $requestoptions = null ) {
		return $this->getReader()->getInProperties( $value, $requestoptions );
	}


///// Writing methods /////


	public function getWriter() {
		if( $this->writer == false ) {
			$this->writer = new SMWSQLStore3Writers( $this );//Initialize if not done already
		}

		return $this->writer;
	}

	public function deleteSubject( Title $subject ) {
		$this->getWriter()->deleteSubject( $subject );
	}

	protected function doDataUpdate( SemanticData $data ) {
		$this->getWriter()->doDataUpdate( $data );
	}

	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		$this->getWriter()->changeTitle( $oldtitle, $newtitle, $pageid, $redirid );
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

		if ( wfRunHooks( 'SMW::Store::BeforeQueryResultLookupComplete', array( $this, $query, &$result ) ) ) {
			$result = $this->fetchQueryResult( $query );
		}

		wfRunHooks( 'SMW::Store::AfterQueryResultLookupComplete', array( $this, &$result ) );

		return $result;
	}

	protected function fetchQueryResult( SMWQuery $query ) {
		return $this->factory->newSlaveQueryEngine()->getQueryResult( $query );
	}

///// Special page functions /////

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return ListLookup
	 */
	public function getPropertiesSpecial( $requestOptions = null ) {

		$propertyUsageListLookup = $this->factory->newPropertyUsageListLookup(
			$requestOptions
		);

		$cachedListLookup = $this->factory->newCachedListLookup(
			$propertyUsageListLookup,
			self::$configuration->get( 'smwgPropertiesCache' ),
			self::$configuration->get( 'smwgPropertiesCacheExpiry' )
		);

		return $cachedListLookup;
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return ListLookup
	 */
	public function getUnusedPropertiesSpecial( $requestOptions = null ) {

		$unusedPropertyListLookup = $this->factory->newUnusedPropertyListLookup(
			$requestOptions
		);

		$cachedListLookup = $this->factory->newCachedListLookup(
			$unusedPropertyListLookup,
			self::$configuration->get( 'smwgUnusedPropertiesCache' ),
			self::$configuration->get( 'smwgUnusedPropertiesCacheExpiry' )
		);

		return $cachedListLookup;
	}

	/**
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return ListLookup
	 */
	public function getWantedPropertiesSpecial( $requestOptions = null ) {

		$undeclaredPropertyListLookup = $this->factory->newUndeclaredPropertyListLookup(
			$requestOptions,
			self::$configuration->get( 'smwgPDefaultType' )
		);

		$cachedListLookup = $this->factory->newCachedListLookup(
			$undeclaredPropertyListLookup,
			self::$configuration->get( 'smwgWantedPropertiesCache' ),
			self::$configuration->get( 'smwgWantedPropertiesCacheExpiry' )
		);

		return $cachedListLookup;
	}

	public function getStatistics() {

		$cachedListLookup = $this->factory->newCachedListLookup(
			$this->factory->newUsageStatisticsListLookup(),
			self::$configuration->get( 'smwgStatisticsCache' ),
			self::$configuration->get( 'smwgStatisticsCacheExpiry' )
		);

		return $cachedListLookup->fetchList();
	}


///// Setup store /////

	public function getSetupHandler() {
		if( $this->setupHandler == false ) {
			$this->setupHandler = new SMWSQLStore3SetupHandlers( $this );//Initialize if not done already
		}

		return $this->setupHandler;
	}

	public function setup( $verbose = true ) {
		return $this->getSetupHandler()->setup( $verbose );
	}

	public function drop( $verbose = true ) {
		return $this->getSetupHandler()->drop( $verbose );
	}

	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		return $this->getSetupHandler()->refreshData( $index, $count, $namespaces, $usejobs );
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
	 * Transform input parameters into a suitable array of SQL options.
	 * The parameter $valuecol defines the string name of the column to which
	 * sorting requests etc. are to be applied.
	 *
	 * @since 1.8
	 * @param SMWRequestOptions|null $requestoptions
	 * @param string $valuecol
	 * @return array
	 */
	public function getSQLOptions( SMWRequestOptions $requestoptions = null, $valuecol = '' ) {
		$sql_options = array();

		if ( $requestoptions !== null ) {
			if ( $requestoptions->limit > 0 ) {
				$sql_options['LIMIT'] = $requestoptions->limit;
			}

			if ( $requestoptions->offset > 0 ) {
				$sql_options['OFFSET'] = $requestoptions->offset;
			}

			if ( ( $valuecol !== '' ) && ( $requestoptions->sort ) ) {
				$sql_options['ORDER BY'] = $requestoptions->ascending ? $valuecol : $valuecol . ' DESC';
			}
		}

		return $sql_options;
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL
	 * conditions. The parameter $valuecol defines the string name of the
	 * column to which value restrictions etc. are to be applied.
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
		$sqlConds = '';

		if ( $requestOptions !== null ) {
			$db = wfGetDB( DB_SLAVE ); /// TODO avoid doing this here again, all callers should have one

			if ( ( $valueCol !== '' ) && ( $requestOptions->boundary !== null ) ) { // Apply value boundary.
				if ( $requestOptions->ascending ) {
					$op = $requestOptions->include_boundary ? ' >= ' : ' > ';
				} else {
					$op = $requestOptions->include_boundary ? ' <= ' : ' < ';
				}
				$sqlConds .= ( $addAnd ? ' AND ' : '' ) . $valueCol . $op . $db->addQuotes( $requestOptions->boundary );
			}

			if ( $labelCol !== '' ) { // Apply string conditions.
				foreach ( $requestOptions->getStringConditions() as $strcond ) {
					$string = str_replace( '_', '\_', $strcond->string );

					switch ( $strcond->condition ) {
						case SMWStringCondition::STRCOND_PRE:  $string .= '%';
						break;
						case SMWStringCondition::STRCOND_POST: $string = '%' . $string;
						break;
						case SMWStringCondition::STRCOND_MID:  $string = '%' . $string . '%';
						break;
					}

					$sqlConds .= ( ( $addAnd || ( $sqlConds !== '' ) ) ? ' AND ' : '' ) . $labelCol . ' LIKE ' . $db->addQuotes( $string );
				}
			}
		}

		return $sqlConds;
	}

	/**
	 * Not in all cases can requestoptions be forwarded to the DB using
	 * getSQLConditions() and getSQLOptions(): some data comes from caches
	 * that do not respect the options yet. This method takes an array of
	 * results (SMWDataItem objects) *of the same type* and applies the
	 * given requestoptions as appropriate.
	 *
	 * @since 1.8
	 * @param array $data array of SMWDataItem objects
	 * @param SMWRequestOptions|null $requestoptions
	 * @return SMWDataItem[]
	 */
	public function applyRequestOptions( array $data, SMWRequestOptions $requestoptions = null ) {

		if ( ( count( $data ) == 0 ) || is_null( $requestoptions ) ) {
			return $data;
		}

		$result = array();
		$sortres = array();

		$sampleDataItem = reset( $data );
		$numeric = is_numeric( $sampleDataItem->getSortKey() );

		$i = 0;

		foreach ( $data as $item ) {
			$ok = true; // keep datavalue only if this remains true

			if ( $item instanceof DIWikiPage ) {
				$label = $this->getWikiPageSortKey( $item );
				$value = $label;
			} else {
				$label = ( $item instanceof SMWDIBlob ) ? $item->getString() : '';
				$value = $item->getSortKey();
			}

			if ( $requestoptions->boundary !== null ) { // apply value boundary
				$strc = $numeric ? 0 : strcmp( $value, $requestoptions->boundary );

				if ( $requestoptions->ascending ) {
					if ( $requestoptions->include_boundary ) {
						$ok = $numeric ? ( $value >= $requestoptions->boundary ) : ( $strc >= 0 );
					} else {
						$ok = $numeric ? ( $value > $requestoptions->boundary ) : ( $strc > 0 );
					}
				} else {
					if ( $requestoptions->include_boundary ) {
						$ok = $numeric ? ( $value <= $requestoptions->boundary ) : ( $strc <= 0 );
					} else {
						$ok = $numeric ? ( $value < $requestoptions->boundary ) : ( $strc < 0 );
					}
				}
			}

			foreach ( $requestoptions->getStringConditions() as $strcond ) { // apply string conditions
				switch ( $strcond->condition ) {
					case SMWStringCondition::STRCOND_PRE:
						$ok = $ok && ( strpos( $label, $strcond->string ) === 0 );
						break;
					case SMWStringCondition::STRCOND_POST:
						$ok = $ok && ( strpos( strrev( $label ), strrev( $strcond->string ) ) === 0 );
						break;
					case SMWStringCondition::STRCOND_MID:
						$ok = $ok && ( strpos( $label, $strcond->string ) !== false );
						break;
				}
			}

			if ( $ok ) {
				$result[$i] = $item;
				$sortres[$i] = $value;
				$i++;
			}
		}

		if ( $requestoptions->sort ) {
			$flag = $numeric ? SORT_NUMERIC : SORT_LOCALE_STRING;

			if ( $requestoptions->ascending ) {
				asort( $sortres, $flag );
			} else {
				arsort( $sortres, $flag );
			}

			$newres = array();

			foreach ( $sortres as $key => $value ) {
				$newres[] = $result[$key];
			}

			$result = $newres;
		}

		if ( $requestoptions->limit > 0 ) {
			$result = array_slice( $result, $requestoptions->offset, $requestoptions->limit );
		} else {
			$result = array_slice( $result, $requestoptions->offset );
		}


		return $result;
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
		$db = wfGetDB( DB_MASTER );

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

				foreach ( $proptable->getFields( $this ) as $fieldname => $type ) {
					if ( $type == 'p' ) {
						$db->update( $proptable->getName(), array( $fieldname => $newid ), array( $fieldname => $oldid ), __METHOD__ );
					}
				}
			}
		}

		// Change id entries in concept-related tables:
		if ( $sdata && ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_CONCEPT ) ) ) {
			if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_CONCEPT ) ) {
				$db->update( 'smw_fpt_conc', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
				$db->update( SMWSQLStore3::CONCEPT_CACHE_TABLE, array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			} else {
				$db->delete( 'smw_fpt_conc', array( 's_id' => $oldid ), __METHOD__ );
				$db->delete( SMWSQLStore3::CONCEPT_CACHE_TABLE, array( 's_id' => $oldid ), __METHOD__ );
			}
		}

		if ( $podata ) {
			$db->update( SMWSQLStore3::CONCEPT_CACHE_TABLE, array( 'o_id' => $newid ), array( 'o_id' => $oldid ), __METHOD__ );
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
	 * @return mixed
	 */
	public function getConnection( $connectionTypeId = 'mw.db' ) {
		return parent::getConnection( $connectionTypeId );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $updateFeatureFlag
	 *
	 * @return boolean
	 */
	public function canUseUpdateFeature( $updateFeatureFlag ) {
		return $GLOBALS['smwgUFeatures'] === ( $GLOBALS['smwgUFeatures'] | $updateFeatureFlag );
	}

	/**
	 * @note It is performance critical to make sure that the instance is only
	 * invoked once per request
	 *
	 * @since 2.2
	 *
	 * @return PpropertyTableInfoFetcher
	 */
	public function getPropertyTableInfoFetcher() {

		if ( $this->propertyTableInfoFetcher === null ) {
			$this->propertyTableInfoFetcher = new PropertyTableInfoFetcher();

			$this->propertyTableInfoFetcher->setCustomFixedPropertyList(
				self::$configuration->get( 'smwgFixedProperties' )
			);

			$this->propertyTableInfoFetcher->setCustomSpecialPropertyList(
				self::$configuration->get( 'smwgPageSpecialProperties' )
			);
		}

		return $this->propertyTableInfoFetcher;
	}

}
