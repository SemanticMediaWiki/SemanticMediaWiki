<?php

/**
 * SQL-based implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 *
 * @file
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
	 * The SpecialPageHandler object used by this store. Initialized by
	 * getSpecialPageHandler(), which is the only way in which it should
	 * be accessed.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3SpecialPageHandlers
	 */
	protected $specialPageHandler = false;

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
	 * Cache for SMWSemanticData objects, indexed by SMW ID.
	 *
	 * @todo In the future, the cache should be managed by a helper class.
	 *
	 * @since 1.8
	 * @var array
	 */
	public $m_semdata = array();

	/**
	 * Like SMWSQLStore3::m_semdata, but containing flags indicating
	 * completeness of the SMWSemanticData objs.
	 *
	 * @since 1.8
	 * @var array
	 */
	public $m_sdstate = array();

	/**
	 * Array for keeping property table table data, indexed by table id.
	 * Access this only by calling getPropertyTables().
	 *
	 * @since 1.8
	 * @var SMWSQLStore3Table[]
	 */
	protected static $prop_tables;

	/**
	 * Array to cache "propkey => table id" associations for fixed property
	 * tables. Initialized by getPropertyTables(), which must be called
	 * before accessing this.
	 *
	 * @since 1.8
	 * @var array|null
	 */
	protected static $fixedPropertyTableIds = null;

	/**
	 * Keys of special properties that should have their own
	 * fixed property table.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected static $special_tables = array(
		// page metadata tables
		'_MDAT', '_CDAT', '_NEWP', '_LEDT',
		// property declarations
		'_TYPE', '_UNIT', '_CONV', '_PVAL', '_LIST', '_SERV',
		// query statistics (very frequently used)
		'_ASK', '_ASKDE', '_ASKSI', '_ASKFO', '_ASKST',
		// subproperties, classes, and instances
		'_SUBP', '_SUBC', '_INST',
		// redirects
		'_REDI',
		// has sub object
		'_SOBJ',
		// vocabulary import and URI assignments
		'_IMPO', '_URI',
		// Concepts
		'_CONC',
		// Semantic forms properties:
		'_SF_DF', '_SF_AF',
	);

	/**
	 * Default tables to use for storing data of certain types.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected static $di_type_tables = array(
		SMWDataItem::TYPE_NUMBER     => 'smw_di_number',
		SMWDataItem::TYPE_STRING     => 'smw_di_blob',
		SMWDataItem::TYPE_BLOB       => 'smw_di_blob',
		SMWDataItem::TYPE_BOOLEAN    => 'smw_di_bool',
		SMWDataItem::TYPE_URI        => 'smw_di_uri',
		SMWDataItem::TYPE_TIME       => 'smw_di_time',
		SMWDataItem::TYPE_GEO        => 'smw_di_coords', // currently created only if Semantic Maps are installed
		SMWDataItem::TYPE_WIKIPAGE   => 'smw_di_wikipage',
		//SMWDataItem::TYPE_CONCEPT    => '', // _CONC is the only property of this type
	);

	/**
	* These are fixed properties, i.e. user defined tables having a
	* dedicated table for them. Entries in this array have the format
	* property key => property DI type. The key is a DB key (title with
	* underscores instead of _ and capital first letter). The DI type is
	* one of the types declared in SMWDataItem, e.g.,
	* SMWDataItem::TYPE_DATE. The correct DI type for common datatypes
	* can be found in SMWDataValueFactory.
	*
	* See also http://semantic-mediawiki.org/wiki/Fixed_properties
	*
	* @todo Move these to somewhere else?
	*
	* @since 1.8
	* @var array
	*/
	public static $fixedProperties = array();

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 */
	public function __construct() {
		$this->smwIds = new SMWSql3SmwIds( $this );
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
				case SMWDataItem::TYPE_STRING:
					$this->diHandlers[$diType] = new SMWDIHandlerString( $this );
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
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeid );
		return $this->getDataItemHandlerForDIType( $dataItemId );
	}

///// Reading methods /////

	public function getReader() {
		if( $this->reader == false )
			$this->reader = new SMWSQLStore3Readers( $this );//Initialize if not done already

		return $this->reader;
	}

	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		return $this->getReader()->getSemanticData( $subject, $filter );
	}

	public function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null ) {
		return $this->getReader()->getPropertyValues( $subject, $property, $requestoptions );
	}

	public function getPropertySubjects( SMWDIProperty $property, $value, $requestoptions = null ) {
		return $this->getReader()->getPropertySubjects( $property, $value, $requestoptions );
	}

	public function getAllPropertySubjects( SMWDIProperty $property, $requestoptions = null ) {
		return $this->getReader()->getAllPropertySubjects( $property, $requestoptions );
	}

	public function getProperties( SMWDIWikiPage $subject, $requestoptions = null ) {
		return $this->getReader()->getProperties( $subject, $requestoptions );
	}

	public function getInProperties( SMWDataItem $value, $requestoptions = null ) {
		return $this->getReader()->getInProperties( $value, $requestoptions );
	}


///// Writing methods /////


	public function getWriter() {
		if( $this->writer == false )
			$this->writer = new SMWSQLStore3Writers( $this );//Initialize if not done already

		return $this->writer;
	}

	public function deleteSubject ( Title $subject ) {
		return $this->getWriter()->deleteSubject( $subject );
	}

	public function doDataUpdate( SMWSemanticData $data ) {
		return $this->getWriter()->doDataUpdate( $data );
	}

	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		return $this->getWriter()->changeTitle( $oldtitle, $newtitle, $pageid, $redirid );
	}


///// Query answering /////

	/**
	 * @see SMWStore::getQueryResult
	 *
	 * @since 1.8
	 * @param SMWQuery $query
	 * @return SMWQueryResult|string|integer depends on $query->querymode
	 */
	public function getQueryResult( SMWQuery $query ) {
		wfProfileIn( 'SMWSQLStore3::getQueryResult (SMW)' );

		$qe = new SMWSQLStore3QueryEngine( $this, wfGetDB( DB_SLAVE ) );
		$result = $qe->getQueryResult( $query );
		wfProfileOut( 'SMWSQLStore3::getQueryResult (SMW)' );

		return $result;
	}

///// Special page functions /////

	public function getSpecialPageHandler() {
		if( $this->specialPageHandler == false )
			$this->specialPageHandler = new SMWSQLStore3SpecialPageHandlers( $this );//Initialize if not done already

		return $this->specialPageHandler;
	}

	public function getPropertiesSpecial( $requestoptions = null ) {
		return $this->getSpecialPageHandler()->getPropertiesSpecial( $requestoptions );
	}

	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return $this->getSpecialPageHandler()->getUnusedPropertiesSpecial( $requestoptions );
	}

	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return $this->getSpecialPageHandler()->getWantedPropertiesSpecial( $requestoptions );
	}

	public function getStatistics() {
		return $this->getSpecialPageHandler()->getStatistics();
	}


///// Setup store /////

	public function getSetupHandler() {
		if( $this->setupHandler == false )
			$this->setupHandler = new SMWSQLStore3SetupHandlers( $this );//Initialize if not done already

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
		wfProfileIn( 'SMWSQLStore3::refreshConceptCache (SMW)' );

		$qe = new SMWSQLStore3QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->refreshConceptCache( $concept );

		wfProfileOut( 'SMWSQLStore3::refreshConceptCache (SMW)' );

		return $result;
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @since 1.8
	 * @param Title $concept
	 */
	public function deleteConceptCache( $concept ) {
		wfProfileIn( 'SMWSQLStore3::deleteConceptCache (SMW)' );

		$qe = new SMWSQLStore3QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->deleteConceptCache( $concept );

		wfProfileOut( 'SMWSQLStore3::deleteConceptCache (SMW)' );

		return $result;
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
	 * @return array
	 */
	public function getConceptCacheStatus( $concept ) {
		wfProfileIn( 'SMWSQLStore3::getConceptCacheStatus (SMW)' );

		$db = wfGetDB( DB_SLAVE );
		$cid = $this->smwIds->getSMWPageID( $concept->getDBkey(), $concept->getNamespace(), '', '', false );

		$row = $db->selectRow( 'smw_fpt_conc',
		         array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date', 'cache_count' ),
		         array( 's_id' => $cid ), 'SMWSQLStore3::getConceptCacheStatus (SMW)' );

		if ( $row !== false ) {
			$result = array( 'size' => $row->concept_size, 'depth' => $row->concept_depth, 'features' => $row->concept_features );

			if ( $row->cache_date ) {
				$result['status'] = 'full';
				$result['date'] = $row->cache_date;
				$result['count'] = $row->cache_count;
			} else {
				$result['status'] = 'empty';
			}
		} else {
			$result = array( 'status' => 'no' );
		}

		wfProfileOut( 'SMWSQLStore3::getConceptCacheStatus (SMW)' );

		return $result;
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
	 * @param SMWRequestOptions|null $requestoptions
	 * @param string $valuecol name of SQL column to which conditions apply
	 * @param string $labelcol name of SQL column to which string conditions apply, if any
	 * @param boolean $addand indicate whether the string should begin with " AND " if non-empty
	 * @return string
	 */
	public function getSQLConditions( SMWRequestOptions $requestoptions = null, $valuecol = '', $labelcol = '', $addand = true ) {
		$sql_conds = '';

		if ( $requestoptions !== null ) {
			$db = wfGetDB( DB_SLAVE ); /// TODO avoid doing this here again, all callers should have one

			if ( ( $valuecol !== '' ) && ( $requestoptions->boundary !== null ) ) { // Apply value boundary.
				if ( $requestoptions->ascending ) {
					$op = $requestoptions->include_boundary ? ' >= ' : ' > ';
				} else {
					$op = $requestoptions->include_boundary ? ' <= ' : ' < ';
				}
				$sql_conds .= ( $addand ? ' AND ' : '' ) . $valuecol . $op . $db->addQuotes( $requestoptions->boundary );
			}

			if ( $labelcol !== '' ) { // Apply string conditions.
				foreach ( $requestoptions->getStringConditions() as $strcond ) {
					$string = str_replace( '_', '\_', $strcond->string );

					switch ( $strcond->condition ) {
						case SMWStringCondition::STRCOND_PRE:  $string .= '%'; break;
						case SMWStringCondition::STRCOND_POST: $string = '%' . $string; break;
						case SMWStringCondition::STRCOND_MID:  $string = '%' . $string . '%'; break;
					}

					$sql_conds .= ( ( $addand || ( $sql_conds !== '' ) ) ? ' AND ' : '' ) . $labelcol . ' LIKE ' . $db->addQuotes( $string );
				}
			}
		}

		return $sql_conds;
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
	 * @return array of SMWDataItem objects
	 */
	public function applyRequestOptions( array $data, SMWRequestOptions $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore3::applyRequestOptions (SMW)" );

		if ( ( count( $data ) == 0 ) || is_null( $requestoptions ) ) {
			wfProfileOut( "SMWSQLStore3::applyRequestOptions (SMW)" );
			return $data;
		}

		$result = array();
		$sortres = array();

		$sampleDataItem = reset( $data );
		$numeric = is_numeric( $sampleDataItem->getSortKey() );

		$i = 0;

		foreach ( $data as $item ) {
			$ok = true; // keep datavalue only if this remains true

			if ( $item instanceof SMWDIWikiPage ) {
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

		wfProfileOut( "SMWSQLStore3::applyRequestOptions (SMW)" );

		return $result;
	}

	/**
	 * Find the id of a property table that is suitable for storing values of
	 * the given type. The type is specified by an SMW type id such as '_wpg'.
	 * An empty string is returned if no matching table could be found.
	 *
	 * @since 1.8
	 * @param string $typeid
	 * @return string
	 */
	public static function findTypeTableId( $typeid ) {
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeid );
		return self::findDiTypeTableId( $dataItemId );
	}

	/**
	 * Find the id of a property table that is normally used to store
	 * data items of the given type. The empty string is returned if
	 * no such table exists.
	 *
	 * @since 1.8
	 * @param integer $dataItemId
	 * @return string
	 */
	public static function findDiTypeTableId( $dataItemId ) {
		if ( array_key_exists( $dataItemId, self::$di_type_tables ) ) {
			return self::$di_type_tables[$dataItemId];
		} else {
			return '';
		}
	}

	/**
	 * Retrieve the id of the property table that is to be used for storing
	 * values for the given property object.
	 *
	 * @since 1.8
	 * @param SMWDIProperty $diProperty
	 * @return string
	 */
	public static function findPropertyTableID( SMWDIProperty $diProperty ) {
		$propertyKey = $diProperty->getKey();

		// This is needed to initialize the $fixedPropertyTableIds field
		self::getPropertyTables();

		if ( array_key_exists( $propertyKey, self::$fixedPropertyTableIds ) ) {
			return self::$fixedPropertyTableIds[$propertyKey];
		} else {
			return self::findTypeTableId( $diProperty->findPropertyTypeID() );
		}
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
		foreach ( self::getPropertyTables() as $proptable ) {
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
	 * Return the array of predefined property table declarations, initialising
	 * it if necessary. The result is an array of SMWSQLStore3Table objects
	 * indexed by table ids.
	 *
	 * It is ensured that the keys of the returned array agree with the name of
	 * the table that they refer to.
	 *
	 * @since 1.8
	 * @return SMWSQLStore3Table[]
	 */
	public static function getPropertyTables() {
		if ( isset( self::$prop_tables ) ) {
			return self::$prop_tables; // Don't initialise twice.
		}

		/**
		 * @var SMWSQLStore3Table[] $propertyTables
		 */
		$propertyTables = array();

		//tables for each DI type
		foreach( self::$di_type_tables as $tableDIType => $tableName ){
			$propertyTables[$tableName] = new SMWSQLStore3Table( $tableDIType, $tableName );
		}

		//tables for special properties
		foreach( self::$special_tables as $propertyKey ){
			$typeId = SMWDIProperty::getPredefinedPropertyTypeId( $propertyKey );
			$diType = SMWDataValueFactory::getDataItemId( $typeId );
			$tableName = 'smw_fpt' . strtolower( $propertyKey );
			$propertyTables[$tableName] = new SMWSQLStore3Table( $diType, $tableName, $propertyKey );
		}

		// Redirect table uses another subject scheme for historic reasons
		// TODO This should be changed if possible
		$propertyTables['smw_fpt_redi']->setUsesIdSubject( false );

		// Get all the tables for the properties that are declared as fixed
		// (overly used and thus having separate tables)
		foreach( self::$fixedProperties as $propertyKey => $tableDIType ){
			$tableName = 'smw_fpt_' . md5( $propertyKey );
			$propertyTables[$tableName] = new SMWSQLStore3Table( $tableDIType, $tableName, $propertyKey );
		}

		wfRunHooks( 'SMWPropertyTables', array( &$propertyTables ) );

		self::$prop_tables = $propertyTables;

		// Build index for finding property tables
		self::$fixedPropertyTableIds = array();

		foreach ( self::$prop_tables as $tid => $propTable ) {
			if ( $propTable->isFixedPropertyTable() ) {
				self::$fixedPropertyTableIds[$propTable->getFixedProperty()] = $tid;
			}
		}

		// Specifically set properties that must not be stored in any
		// property table to null here. Any function that hits this
		// null unprepared is doing something wrong anyway.
		self::$fixedPropertyTableIds['_SKEY'] = null;

		return self::$prop_tables;
	}

}
