<?php

/**
 * New SQL implementation of SMW's storage abstraction layer.
 * TODO: Check what needs to be protected and make them protected.
 * ( Most helper methods and pre-defined property arrays were made public to support the storerewrite)
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
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
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 *
 * @note Regarding the use of interwiki links in the store, there is currently
 * no support for storing semantic data about interwiki objects, and hence queries
 * that involve interwiki objects really make sense only for them occurring in
 * object positions. Most methods still use the given input interwiki text as a simple
 * way to filter out results that may be found if an interwiki object is given but a
 * local object of the same name exists. It is currently not planned to support things
 * like interwiki reuse of properties.
 * @ingroup SMWStore
 */
class SMWSQLStore3 extends SMWStore {

	/// Cache for SMW IDs
	public $m_idCache;

	/**
	 * The reader object used by this store. Initialized by getReader()
	 * Always access using getReader()
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3Readers
	 */
	protected $reader = false;

	/**
	 * The writer object used by this store. Initialized by getWriter()
	 * Always access using getWriter()
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3Writers
	 */
	protected $writer = false;

	/**
	 * The SpecialPageHandler object used by this store. Initialized by getSpecialPageHandler()
	 * Always access using getSpecialPageHandler()
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3SpecialPageHandlers
	 */
	protected $specialPageHandler = false;

	/**
	 * The SetupHandler object used by this store. Initialized by getSetupHandler()
	 * Always access using getSetupHandler()
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3SetupHandlers
	 */
	protected $setupHandler = false;


	/// Cache for SMWSemanticData objects, indexed by SMW ID
	public $m_semdata = array();
	/// Like SMWSQLStore3::m_semdata, but containing flags indicating completeness of the SMWSemanticData objs
	public $m_sdstate = array();

	/// Array for keeping property table table data, indexed by table id.
	/// Access this only by calling getPropertyTables().
	public static $prop_tables = array();
	/// Array to cache "propkey => table id" associations for fixed property tables. Built only when needed.
	public static $fixed_prop_tables = null;

	/// Use pre-defined ids for Very Important Properties, avoiding frequent ID lookups for those
	public static $special_ids = array(
		'_TYPE' => 1,
		'_URI'  => 2,
		'_INST' => 4,
		'_UNIT' => 7,
		'_IMPO' => 8,
		'_CONV' => 12,
		'_SERV' => 13,
		'_PVAL' => 14,
		'_REDI' => 15,
		'_SUBP' => 17,
		'_SUBC' => 18,
		'_CONC' => 19,
		'_SF_DF' => 20, // Semantic Form's default form property
		'_SF_AF' => 21,  // Semantic Form's alternate form property
		'_ERRP' => 22,
// 		'_1' => 23, // properties for encoding (short) lists
// 		'_2' => 24,
// 		'_3' => 25,
// 		'_4' => 26,
// 		'_5' => 27,
		'_LIST' => 28,
		'_MDAT' => 29,
		'_CDAT' => 30,
		'_NEWP' => 31,
		'_LEDT' => 32,
	);

	/// Use special tables for Very Important Properties
	public static $special_tables = array(
		'_TYPE' => 'smw_type',
		'_URI'  => 'smw_uri',
		'_INST' => 'smw_inst',
		'_UNIT' => 'smw_unit',
		'_IMPO' => 'smw_impo',
		'_CONV' => 'smw_conv',
		'_SERV' => 'smw_serv',
		'_PVAL' => 'smw_pval',
		'_REDI' => 'smw_redi',
		'_SUBP' => 'smw_subp',
		'_SUBC' => 'smw_subs',
		'_CONC' => 'smw_conc',
		'_SF_DF' => 'smw_sfdf', // Semantic Form's default form property
		'_SF_AF' => 'smw_sfaf',  // Semantic Form's alternate form property
		'_MDAT'  => 'smw_mdat',
		'_CDAT'  => 'smw_cdat',
		//'_ERRP', '_SKEY' // no special table
		'_LIST' => 'smw_list',
	);

	/// Default tables to use for storing data of certain types.
	public static $di_type_tables = array(
		SMWDataItem::TYPE_NUMBER     => 'smw_di_number',
		SMWDataItem::TYPE_STRING     => 'smw_di_blob',
		SMWDataItem::TYPE_BLOB       => 'smw_di_blob',
		SMWDataItem::TYPE_BOOLEAN    => 'smw_di_bool',
		SMWDataItem::TYPE_URI        => 'smw_di_uri',
		SMWDataItem::TYPE_TIME       => 'smw_di_time',
		SMWDataItem::TYPE_GEO        => 'smw_di_coords', // currently created only if Semantic Maps are installed
		SMWDataItem::TYPE_CONTAINER  => 'smw_di_container', // values of this type represented by internal objects, stored like pages in smw_rels2
		SMWDataItem::TYPE_WIKIPAGE   => 'smw_di_wikipage',
		SMWDataItem::TYPE_CONCEPT    => 'smw_conc', // unlikely to occur as value of a normal property
		SMWDataItem::TYPE_PROPERTY   => 'smw_di_property'  // unlikely to occur as value of any property
	);

	/*
	* These are fixed properties i.e. user defined tables having a dedicated table for them.
	* Declare these properties as an array of their name (as seen on the wiki) => SMWDataItem::TYPE_proptype
	* where proptype should be replaced with the appropriate type as seen in the array above
	* Example usage 	'Age' => SMWDataItem::TYPE_NUMBER,
	* TODO - Document on semantic-mediawiki.org , move these to somewhere else
	*
	* @since storerewrite
	*/
	public static $fixedProperties = array(
	);

	public function __construct() {
		$this->m_idCache = new SMWSQLStore3IdCache( wfGetDB( DB_SLAVE ) );
	}

///// Reading methods /////

	public function getReader() {
		if( $this->reader == false )
			$this->reader = new SMWSQLStore3Readers( $this );//Initialize if not done already

		return $this->reader;
	}

	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		$result = $this->getReader()->getSemanticData( $subject, $filter );

		return $result;
	}

	public function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null ) {
		$result = $this->getReader()->getPropertyValues( $subject, $property, $requestoptions );

		return $result;
	}

	public function getPropertySubjects( SMWDIProperty $property, $value, $requestoptions = null ) {
		$result = $this->getReader()->getPropertySubjects( $property, $value, $requestoptions );

		return $result;
	}

	public function getAllPropertySubjects( SMWDIProperty $property, $requestoptions = null ) {
		$result = $this->getReader()->getAllPropertySubjects( $property, $requestoptions );

		return $result;
	}

	public function getProperties( SMWDIWikiPage $subject, $requestoptions = null ) {
		$result = $this->getReader()->getProperties( $subject, $requestoptions );

		return $result;
	}

	public function getInProperties( SMWDataItem $value, $requestoptions = null ) {
		$result = $this->getReader()->getInProperties( $value, $requestoptions );

		return $result;
	}


///// Writing methods /////


	public function getWriter() {
		if( $this->writer == false )
			$this->writer = new SMWSQLStore3Writers( $this );//Initialize if not done already

		return $this->writer;
	}

	public function deleteSubject ( Title $subject ) {
		$result = $this->getWriter()->deleteSubject( $subject );

		return $result;
	}

	public function doDataUpdate( SMWSemanticData $data ) {
		$result = $this->getWriter()->doDataUpdate( $data );

		return $result;
	}

	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		$result = $this->getWriter()->changeTitle( $oldtitle, $newtitle, $pageid, $redirid );

		return $result;
	}


///// Query answering /////

	/**
	 * @see SMWStore::getQueryResult
	 *
	 * @param $query SMWQuery
	 *
	 * @return mixed: depends on $query->querymode
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
		$result = $this->getSpecialPageHandler()->getPropertiesSpecial( $requestoptions );

		return $result;
	}

	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		$result = $this->getSpecialPageHandler()->getUnusedPropertiesSpecial( $requestoptions );

		return $result;
	}

	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		$result = $this->getSpecialPageHandler()->getWantedPropertiesSpecial( $requestoptions );

		return $result;
	}

	public function getStatistics() {
		$result = $this->getSpecialPageHandler()->getStatistics();

		return $result;
	}


///// Setup store /////

	public function getSetupHandler() {
		if( $this->setupHandler == false )
			$this->setupHandler = new SMWSQLStore3SetupHandlers( $this );//Initialize if not done already

		return $this->setupHandler;
	}

	public function setup( $verbose = true ) {
		$result = $this->getSetupHandler()->setup( $verbose );

		return $result;
	}

	public function drop( $verbose = true ) {
		$result = $this->getSetupHandler()->drop( $verbose );

		return $result;
	}

	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		$result = $this->getSetupHandler()->refreshData( $index, $count, $namespaces, $usejobs );

		return $result;
	}


///// Concept caching /////

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 *
	 * @return array
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
	 * @param $concept Title
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
	 * @param $concept Title or SMWWikiPageValue
	 */
	public function getConceptCacheStatus( $concept ) {
		wfProfileIn( 'SMWSQLStore3::getConceptCacheStatus (SMW)' );

		$db = wfGetDB( DB_SLAVE );
		$cid = $this->getSMWPageID( $concept->getDBkey(), $concept->getNamespace(), '', '', false );

		$row = $db->selectRow( 'smw_conc',
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
	 *
	 */
	public function getSQLOptions( $requestoptions, $valuecol = '' ) {
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
	 * @param $requestoptions object with options
	 * @param $valuecol string name of SQL column to which conditions apply
	 * @param $labelcol string name of SQL column to which string conditions apply, if any
	 * @param $addand boolean to indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public function getSQLConditions( $requestoptions, $valuecol = '', $labelcol = '', $addand = true ) {
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
	 */
	public function applyRequestOptions( $data, $requestoptions ) {
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
	 * For a given SMW type id, obtain the "signature" from which the
	 * appropriate property table and information about sorting/filtering
	 * data of this type can be obtained. The result is an array of two
	 * entries: the value field and the label field.
	 */
	public static function getTypeSignature( $typeid ) {
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeid );
		$diHandler = SMWDIHandlerFactory::getDataItemHandlerForDIType( $dataItemId );
		return array(
			$diHandler->getIndexField(),
			$diHandler->getLabelField()
		);
	}

	/**
	 * Check if the given table can be used to store values of the given
	 * type. This is needed to apply the type-based filtering in
	 * getSemanticData().
	 *
	 * @param $tableId string
	 * @param $typeId string
	 * @return boolean
	 */
	public static function tableFitsType( $tableId, $typeId ) {
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeId );
		if ( $tableId == self::findDiTypeTableId( $dataItemId ) ) {
			return true;
		}
		foreach ( self::$fixedProperties as $propertyLabel => $tableDItype ){
			if( self::findFixedPropertyTableID($propertyLabel) == $tableId && $tableDItype == $dataItemId){
				return true;
			}
		}
		foreach ( self::$special_tables as $propertyKey => $specialTableId ) {
			if ( $specialTableId == $tableId ) {
				$diProperty = new SMWDIProperty( $propertyKey, false );
				$propertyTypeId = $diProperty->findPropertyTypeId();
				if ( $typeId == $propertyTypeId ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Find the id of a property table that is suitable for storing values of
	 * the given type. The type is specified by an SMW type id such as '_wpg'.
	 * An empty string is returned if no matching table could be found.
	 *
	 * @param $typeid string
	 * @return string
	 */
	public static function findTypeTableId( $typeid ) {
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeid );
		return self::findDiTypeTableId( $dataItemId );
	}

	/**
	 * Find the id of a property table that is normally used to store
	 * data items of the given type.
	 *
	 * @param $dataItemId integer
	 * @return string
	 */
	public static function findDiTypeTableId( $dataItemId ) {
		return self::$di_type_tables[$dataItemId];
	}

	/**
	 * Find the id of all property tables where data items of the given
	 * type could possibly be stored.
	 *
	 * @param $dataItemId integer
	 * @return array of string
	 */
	public static function findAllDiTypeTableIds( $dataItemId ) {
		$result = array( self::findDiTypeTableId( $dataItemId ) );

		foreach ( self::$special_tables as $specialTableId ) {
			if ( $dataItemId == SMWDataValueFactory::getDataItemId( $dataItemId ) ) {
				$result[] = $specialTableId;
			}
		}

		return $result;
	}

	/**
	 * Retrieve the id of the property table that is to be used for storing
	 * values for the given property object.
	 *
	 * @param $diProperty SMWDIProperty
	 * @return string
	 */
	public static function findPropertyTableID( SMWDIProperty $diProperty ) {
		$propertyKey = $diProperty->getKey();
		$propertyLabel = $diProperty->getLabel();
		if ( array_key_exists( $propertyKey, self::$special_tables ) ) {
			return self::$special_tables[$propertyKey];
		} elseif ( array_key_exists( $propertyLabel, self::$fixedProperties ) ) {
			return self::findFixedPropertyTableID($propertyLabel);
		} else {
			return self::findTypeTableId( $diProperty->findPropertyTypeID() );
		}
	}

	/**
	 * Retrieve the id of the fixed property table that is used for storing
	 * values for the given property label. Make sure it is called only for
	 * the fixed properties otherwise you have some errors.
	 *
	 * @param $propertyLabel string
	 * @return string
	 */
	public static function findFixedPropertyTableID( $propertyLabel ) {
		return 'smw_fixedproptable'.hash( 'md5' , $propertyLabel );
	}

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find
	 * the canonical alias ID for the given page. If no such ID exists, 0 is
	 * returned.
	 */
	public function getSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true ) {
		global $smwgQEqualitySupport;

		$id = $this->m_idCache->getId( $title, $namespace, $iw, $subobjectName );
		if ( $id == 0 && $smwgQEqualitySupport != SMW_EQ_NONE
			&& $subobjectName === '' && $iw === '' ) {
			$iw = SMW_SQL3_SMWREDIIW;
			$id = $this->m_idCache->getId( $title, $namespace, SMW_SQL3_SMWREDIIW, $subobjectName );
		}

		if ( $id == 0 || !$canonical || $iw != SMW_SQL3_SMWREDIIW ) {
			return $id;
		} else {
			$rediId = $this->getRedirectId( $title, $namespace );
			return $rediId != 0 ? $rediId : $id; // fallback for inconsistent redirect info
		}
	}

	/**
	 * Like getSMWPageID(), but also sets the Call-By-Ref parameter $sort to
	 * the current sortkey.
	 */
	public function getSMWPageIDandSort( $title, $namespace, $iw, $subobjectName, &$sort, $canonical ) {
		wfProfileIn( 'SMWSQLStore3::getSMWPageID (SMW)' );

		global $smwgQEqualitySupport;

		$db = wfGetDB( DB_SLAVE );

		if ( $iw !== '' && !is_null( $iw ) ) { // external page; no need to think about redirects
			$iwCond = 'smw_iw=' . $db->addQuotes( $iw );
		} else {
			$iwCond = '(smw_iw=' . $db->addQuotes( '' ) .
				' OR smw_iw=' . $db->addQuotes( SMW_SQL3_SMWREDIIW ) . ')';
		}

		$row = $db->selectRow( 'smw_ids', array( 'smw_id', 'smw_iw', 'smw_sortkey' ),
			'smw_title=' . $db->addQuotes( $title ) .
			' AND smw_namespace=' . $db->addQuotes( $namespace ) .
			" AND $iwCond AND smw_subobject=" . $db->addQuotes( $subobjectName ),
			__METHOD__ );

		if ( $row !== false ) {
			$sort = $row->smw_sortkey;
			$this->m_idCache->setId( $title, $namespace, $row->smw_iw, $subobjectName, $row->smw_id );

			if ( $row->smw_iw == SMW_SQL3_SMWREDIIW && $canonical &&
				$subobjectName === '' && $smwgQEqualitySupport != SMW_EQ_NONE ) {
				$id = $this->getRedirectId( $title, $namespace );
				$this->m_idCache->setId( $title, $namespace, $iw, $subobjectName, 0 );
			} else {
				$id = $row->smw_id;
			}
		} else {
			$id = 0;
			$this->m_idCache->setId( $title, $namespace, $iw, $subobjectName, 0 );
		}

		wfProfileOut( 'SMWSQLStore3::getSMWPageID (SMW)' );
		return $id;
	}

	public function getRedirectId( $title, $namespace ) {
		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( 'smw_redi', 'o_id',
			array( 's_title' => $title, 's_namespace' => $namespace ), __METHOD__ );
		return ( $row === false ) ? 0 : $row->o_id;
	}

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find
	 * the canonical alias ID for the given page. If no such ID exists, a new
	 * ID is created and returned. In any case, the current sortkey is set to
	 * the given one unless $sortkey is empty.
	 * @note Using this with $canonical==false can make sense, especially when
	 * the title is a redirect target (we do not want chains of redirects).
	 * But it is of no relevance if the title does not have an id yet.
	 */
	public function makeSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $sortkey = '' ) {
		wfProfileIn( 'SMWSQLStore3::makeSMWPageID (SMW)' );

		$oldsort = '';
		if ( $sortkey !== '' ) { // get the old sortkey (requires DB access):
			$id = $this->getSMWPageIDandSort( $title, $namespace, $iw, $subobjectName, $oldsort, $canonical );
		} else { // only get the id, can use caches:
			$id = $this->getSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical );
		}

		if ( $id == 0 ) {
			$db = wfGetDB( DB_MASTER );
			$sortkey = $sortkey ? $sortkey : ( str_replace( '_', ' ', $title ) );

			$db->insert(
				'smw_ids',
				array(
					'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
					'smw_title' => $title,
					'smw_namespace' => $namespace,
					'smw_iw' => $iw,
					'smw_subobject' => $subobjectName,
					'smw_sortkey' => $sortkey
				),
				__METHOD__
			);

			$id = $db->insertId();

			$this->m_idCache->setId( $title, $namespace, $iw, $subobjectName, $id );
		} elseif ( ( $sortkey !== '' ) && ( $sortkey != $oldsort ) ) {
			$db = wfGetDB( DB_MASTER );
			$db->update( 'smw_ids', array( 'smw_sortkey' => $sortkey ), array( 'smw_id' => $id ), __METHOD__ );
		}

		wfProfileOut( 'SMWSQLStore3::makeSMWPageID (SMW)' );
		return $id;
	}

	/**
	 * Properties have a mechanisms for being predefined (i.e. in PHP instead
	 * of in wiki). Special "interwiki" prefixes separate the ids of such
	 * predefined properties from the ids for the current pages (which may,
	 * e.g., be moved, while the predefined object is not movable).
	 */
	public function getPropertyInterwiki( SMWDIProperty $property ) {
		return ( $property->getLabel() !== '' ) ? '' : SMW_SQL3_SMWINTDEFIW;
	}

	/**
	 * This function does the same as getSMWPageID() but takes into account
	 * that properties might be predefined.
	 */
	public function getSMWPropertyID( SMWDIProperty $property ) {
		if ( ( !$property->isUserDefined() ) && ( array_key_exists( $property->getKey(), self::$special_ids ) ) ) {
			return self::$special_ids[$property->getKey()]; // very important property with fixed id
		} else {
			return $this->getSMWPageID( $property->getKey(), SMW_NS_PROPERTY, $this->getPropertyInterwiki( $property ), '', true );
		}
	}

	/**
	 * This function does the same as makeSMWPageID() but takes into account
	 * that properties might be predefined.
	 */
	public function makeSMWPropertyID( SMWDIProperty $property ) {
		if ( ( !$property->isUserDefined() ) && ( array_key_exists( $property->getKey(), self::$special_ids ) ) ) {
			return self::$special_ids[$property->getKey()]; // very important property with fixed id
		} else {
			return $this->makeSMWPageID( $property->getKey(), SMW_NS_PROPERTY,
				$this->getPropertyInterwiki( $property ), '', true );
		}
	}

	/**
	 * Extend the ID cache as specified. This is called in places where IDs are
	 * retrieved by SQL queries and it would be a pity to throw them away. This
	 * function expects to get the contents of a row in smw_ids, i.e. possibly
	 * with iw being SMW_SQL3_SMWREDIIW. This information is used to determine
	 * whether the given ID is canonical or not.
	 */
	public function cacheSMWPageID( $id, $title, $namespace, $iw, $subobjectName ) {
		$this->m_idCache->setId( $title, $namespace, $iw, $subobjectName, $id );
	}

	/**
	 * Change an internal id to another value. If no target value is given, the
	 * value is changed to become the last id entry (based on the automatic id
	 * increment of the database). Whatever currently occupies this id will be
	 * moved consistently in all relevant tables. Whatever currently occupies
	 * the target id will be ignored (it should be ensured that nothing is
	 * moved to an id that is still in use somewhere).
	 */
	public function moveSMWPageID( $curid, $targetid = 0 ) {
		$db = wfGetDB( DB_MASTER );

		$row = $db->selectRow( 'smw_ids', '*', array( 'smw_id' => $curid ), __METHOD__ );

		if ( $row === false ) return; // no id at current position, ignore

		if ( $targetid == 0 ) { // append new id
			$db->insert(
				'smw_ids',
				array(
					'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
					'smw_title' => $row->smw_title,
					'smw_namespace' => $row->smw_namespace,
					'smw_iw' => $row->smw_iw,
					'smw_subobject' => $row->smw_subobject,
					'smw_sortkey' => $row->smw_sortkey
				),
				__METHOD__
			);
			$targetid = $db->insertId();
		} else { // change to given id
			$db->insert( 'smw_ids',
				array( 'smw_id' => $targetid,
					'smw_title' => $row->smw_title,
					'smw_namespace' => $row->smw_namespace,
					'smw_iw' => $row->smw_iw,
					'smw_subobject' => $row->smw_subobject,
					'smw_sortkey' => $row->smw_sortkey
				),
				__METHOD__
			);
		}

		$db->delete( 'smw_ids', array( 'smw_id' => $curid ), 'SMWSQLStore3::moveSMWPageID' );

		$this->m_idCache->setId( $row->smw_title, $row->smw_namespace, $row->smw_iw,
			$row->smw_subobject, $targetid );

		$this->changeSMWPageID( $curid, $targetid, $row->smw_namespace, $row->smw_namespace );
	}

	/**
	 * Change an SMW page id across all relevant tables. The redirect table
	 * is also updated (without much effect if the change happended due to
	 * some redirect, since the table should not contain the id of the
	 * redirected page). If namespaces are given, then they are used to
	 * delete any entries that are limited to one particular namespace (e.g.
	 * only properties can be used as properties) instead of moving them.
	 *
	 * The id in smw_ids as such is not touched.
	 *
	 * @note This method only changes internal page IDs in SMW. It does not
	 * assume any change in (title-related) data, as e.g. in a page move.
	 * Internal objects (subobject) do not need to be updated since they
	 * refer to the title of their parent page, not to its ID.
	 *
	 * @param $oldid numeric ID that is to be changed
	 * @param $newid numeric ID to which the records are to be changed
	 * @param $oldnamespace namespace of old id's page (-1 to ignore it)
	 * @param $newnamespace namespace of new id's page (-1 to ignore it)
	 * @param $sdata boolean stating whether to update subject references
	 * @param $podata boolean stating if to update property/object references
	 */
	public function changeSMWPageID( $oldid, $newid, $oldnamespace = -1,
				$newnamespace = -1, $sdata = true, $podata = true ) {
		$db = wfGetDB( DB_MASTER );

		// Change all id entries in property tables:
		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $sdata && $proptable->idsubject ) {
				$db->update( $proptable->name, array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			}

			if ( $podata ) {
				if ( ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_PROPERTY ) ) && ( $proptable->fixedproperty == false ) ) {
					if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_PROPERTY ) ) {
						$db->update( $proptable->name, array( 'p_id' => $newid ), array( 'p_id' => $oldid ), __METHOD__ );
					} else {
						$db->delete( $proptable->name, array( 'p_id' => $oldid ), __METHOD__ );
					}
				}

				foreach ( $proptable->getFields() as $fieldname => $type ) {
					if ( $type == 'p' ) {
						$db->update( $proptable->name, array( $fieldname => $newid ), array( $fieldname => $oldid ), __METHOD__ );
					}
				}
			}
		}

		// Change id entries in concept-related tables:
		if ( $sdata && ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_CONCEPT ) ) ) {
			if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_CONCEPT ) ) {
				$db->update( 'smw_conc', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
				$db->update( 'smw_conccache', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			} else {
				$db->delete( 'smw_conc', array( 's_id' => $oldid ), __METHOD__ );
				$db->delete( 'smw_conccache', array( 's_id' => $oldid ), __METHOD__ );
			}
		}

		if ( $podata ) {
			$db->update( 'smw_conccache', array( 'o_id' => $newid ), array( 'o_id' => $oldid ), __METHOD__ );
		}
	}

	/**
	 * Delete all semantic data stored for the given subject. Used for
	 * update purposes.
	 *
	 * @param $subject SMWDIWikiPage the data of which is deleted
	 */
	public function deleteSemanticData( SMWDIWikiPage $subject ) {
		if ( $subject->getSubobjectName() !== '' ) return; // not needed, and would mess up data

		$db = wfGetDB( DB_MASTER );

		$id = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );

		if ( $id == 0 ) {
			// not (directly) used anywhere yet, may be a redirect but we do not care here
			wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
			return;
		}

		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $proptable->name == 'smw_conc' ) continue; // skip concepts, since they have chache data in their table which should be kept while the cache is intact
			if ( $proptable->idsubject ) {
				$db->delete( $proptable->name, array( 's_id' => $id ), __METHOD__ );
			} elseif ( $proptable->name != 'smw_redi' ) { /// NOTE: redirects are handled by updateRedirects(), not here!
				$db->delete( $proptable->name, array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() ), __METHOD__ );
			}
		}

		// also find subobjects used by this ID ...
		$res = $db->select( 'smw_ids', '*',
			'smw_title = ' . $db->addQuotes( $subject->getDBkey() ) . ' AND ' .
			'smw_namespace = ' . $db->addQuotes( $subject->getNamespace() ) . ' AND ' .
			'smw_iw = ' . $db->addQuotes( $subject->getInterwiki() ) . ' AND ' .
			'smw_subobject != ' . $db->addQuotes( '' ),
// The below code can be used instead when moving to MW 1.17 (support for '!' in Database::makeList()):
// 			array( 'smw_title' => $subject->getDBkey(),
// 				'smw_namespace' => $subject->getNamespace(),
// 				'smw_iw' => $subject->getInterwiki(),
// 				'smw_subobject!' => array( '' ) ), // ! (NOT) in MW only supported for array values!
			__METHOD__ );
		$subobjects = array();

		// ... and delete them as well
		foreach ( $res as $row ) {
			$subobjects[] = $row->smw_id;
			$this->m_idCache->setId( $row->smw_title, $row->smw_namespace,
				$row->smw_iw, $row->smw_subobject, 0 ); // deleted below
			foreach ( self::getPropertyTables() as $proptable ) {
				if ( $proptable->idsubject ) {
					$db->delete( $proptable->name, array( 's_id' => $row->smw_id ), __METHOD__ );
				}
			}
		}

		$db->freeResult( $res );

		// free all affected subobjects in one call:
		if ( count( $subobjects ) > 0 ) {
			$db->delete( 'smw_ids',
				array( 'smw_id' => $subobjects),
				__METHOD__ );
		}

		wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
	}

	/**
	* Returns an array of hashes with table names as keys. These
	* hashes are used to compare new data with old data for each
	* property-value table when updating data
	*
	* since SMW.storerewrite
	* @param $sid ID of the page as stored in smw_ids
	* @return array
	*/
	public function getPropTableHashes( $sid ) {
		$db = wfGetDB( DB_SLAVE );

		$row = $db->selectRow(
			'smw_ids',
			array( 'smw_proptable_hash' ),
			'smw_id=' . $sid ,
			__METHOD__
		);

		if( $row === false && !is_null( $row->smw_proptable_hash ) ) {
			$tableHashes = unserialize( $row->smw_proptable_hash );
		}
		else {
			$tableHashes = array();
		}

		return $tableHashes;
	}

	/**
	* Updates the proptable_hash for a given page.
	*
	* since SMW.storerewrite
	* @param $sid ID of the page as stored in smw_ids
	* @param array() of hash values with tablename as keys
	*/
	public function setPropTableHashes( $sid, array $newTableHashes ) {
		$db = wfGetDB( DB_MASTER );
		$db->update(
			'smw_ids',
			array( 'smw_proptable_hash' => serialize( $newTableHashes ) ),
			array( 'smw_id' => $sid ),
			__METHOD__
		);
	}

	/**
	 * Helper method to write information about some redirect. Various updates
	 * can be necessary if redirects are resolved as identities in SMW. The
	 * title and namespace of the affected page and of its updated redirect
	 * target are given. The target can be empty ('') to delete any redirect.
	 * Returns the canonical ID that is now to be used for the subject.
	 *
	 * This method does not change the ids of the affected pages, and thus it
	 * is not concerned with updates of the data that is currently stored for
	 * the subject. Normally, a subject that is a redirect will not have other
	 * data, but this method does not depend on this.
	 *
	 * @note Please make sure you fully understand this code before making any
	 * changes here. Keeping the redirect structure consistent is important,
	 * and errors in this code can go unnoticed for quite some time.
	 *
	 * @note This method merely handles the addition or deletion of a redirect
	 * statement in the wiki. It does not assume that any page contents has
	 * been changed (e.g. moved). See changeTitle() for additional handling in
	 * this case.
	 */
	public function updateRedirects( $subject_t, $subject_ns, $curtarget_t = '', $curtarget_ns = -1 ) {
		global $smwgQEqualitySupport, $smwgEnableUpdateJobs;

		// *** First get id of subject, old redirect target, and current (new) redirect target ***//

		$sid = $this->getSMWPageID( $subject_t, $subject_ns, '', '', false ); // find real id of subject, if any
		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$new_tid = $curtarget_t ? ( $this->makeSMWPageID( $curtarget_t, $curtarget_ns, '', '', false ) ) : 0; // real id of new target, if given

		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( array( 'smw_redi' ), 'o_id',
				array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), __METHOD__ );
		$old_tid = ( $row !== false ) ? $row->o_id : 0; // real id of old target, if any
		/// NOTE: $old_tid and $new_tid both (intentionally) ignore further redirects: no redirect chains

		if ( $old_tid == $new_tid ) { // no change, all happy
			return ( $new_tid == 0 ) ? $sid : $new_tid;
		} // note that this means $old_tid != $new_tid in all cases below

		// *** Make relevant changes in property tables (don't write the new redirect yet) ***//

		$db = wfGetDB( DB_MASTER ); // now we need to write something

		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->changeSMWPageID( $sid, $new_tid, $subject_ns, $curtarget_ns, false, true );
		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted
			$db->delete( 'smw_redi',
				array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), __METHOD__ );

			if ( $smwgEnableUpdateJobs && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// entries that refer to old target may in fact refer to subject,
				// but we don't know which: schedule affected pages for update
				$jobs = array();

				foreach ( self::getPropertyTables() as $proptable ) {
					if ( $proptable->name == 'smw_redi' ) continue; // can safely be skipped

					if ( $proptable->idsubject ) {
						$from   = $db->tableName( $proptable->name ) . ' INNER JOIN ' .
							  $db->tableName( 'smw_ids' ) . ' ON s_id=smw_id';
						$select = 'DISTINCT smw_title AS t,smw_namespace AS ns';
					} else {
						$from   = $db->tableName( $proptable->name );
						$select = 'DISTINCT s_title AS t,s_namespace AS ns';
					}

					if ( $subject_ns == SMW_NS_PROPERTY && !$proptable->fixedproperty ) {
						$res = $db->select( $from, $select,
							array( 'p_id' => $old_tid ), __METHOD__ );
						foreach ( $res as $row ) {
							$title = Title::makeTitleSafe( $row->ns, $row->t );
							if ( !is_null( $title ) ) {
								$jobs[] = new SMWUpdateJob( $title );
							}
						}
						$db->freeResult( $res );
					}

					foreach ( $proptable->getFields() as $fieldname => $type ) {
						if ( $type == 'p' ) {
							$res = $db->select( $from, $select,
								array( $fieldname => $old_tid ), __METHOD__ );
							foreach ( $res as $row ) {
								$title = Title::makeTitleSafe( $row->ns, $row->t );
								if ( !is_null( $title ) ) {
									$jobs[] = new SMWUpdateJob( $title );
								}
							}
							$db->freeResult( $res );
						}
					}
				}

				/// NOTE: we do not update the concept cache here; this remains an offline task

				/// NOTE: this only happens if $smwgEnableUpdateJobs was true above:
				Job::batchInsert( $jobs );
			}
		}

		// *** Finally, write the new redirect data ***//

		if ( $new_tid != 0 ) { // record a new redirect
			// Redirecting done right:
			// (1) make a new ID with iw SMW_SQL3_SMWREDIIW or
			//     change iw field of current ID in this way,
			// (2) write smw_redi table,
			// (3) update canonical cache.
			// This order must be obeyed unless you really understand what you are doing!

			if ( ( $old_tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// mark subject as redirect (if it was no redirect before)
				if ( $sid == 0 ) { // every redirect page must have an ID
					$sid = $this->makeSMWPageID( $subject_t, $subject_ns,
						SMW_SQL3_SMWREDIIW, '', false );
				} else {
					$db->update( 'smw_ids', array( 'smw_iw' => SMW_SQL3_SMWREDIIW ),
						array( 'smw_id' => $sid ), __METHOD__ );
					$this->m_idCache->setId( $subject_t, $subject_ns, '', '', 0 );
					$this->m_idCache->setId( $subject_t, $subject_ns, SMW_SQL3_SMWREDIIW, '', $sid );
				}
			}

			$db->insert( 'smw_redi', array( 's_title' => $subject_t,
				's_namespace' => $subject_ns, 'o_id' => $new_tid ), __METHOD__ );
		} else { // delete old redirect
			// This case implies $old_tid != 0 (or we would have new_tid == old_tid above).
			// Therefore $subject had a redirect, and it must also have an ID.
			// This shows that $sid != 0 here.
			if ( $smwgQEqualitySupport != SMW_EQ_NONE ) { // mark subject as non-redirect
				$db->update( 'smw_ids', array( 'smw_iw' => '' ), array( 'smw_id' => $sid ), __METHOD__ );
				$this->m_idCache->setId( $subject_t, $subject_ns, '', '', $sid );
			}
		}

		// *** Flush some caches to be safe, though they are not essential in runs with redirect updates ***//

		unset( $this->m_semdata[$sid] ); unset( $this->m_semdata[$new_tid] ); unset( $this->m_semdata[$old_tid] );
		unset( $this->m_sdstate[$sid] ); unset( $this->m_sdstate[$new_tid] ); unset( $this->m_sdstate[$old_tid] );

		return ( $new_tid == 0 ) ? $sid : $new_tid;
	}

	/**
	 * Return the array of predefined property table declarations, initialising
	 * it if necessary. The result is an array of SMWSQLStore3Table objects
	 * indexed by table ids. Note that the ids are only for accessing the data
	 * and should not be assumed to agree with the table name.
	 *
	 * @return array of SMWSQLStore3Table
	 * @todo The concept table should make s_id a primary key; make this possible.
	 */
	public static function getPropertyTables() {
		if ( count( self::$prop_tables ) > 0 ) {
			return self::$prop_tables; // Don't initialise twice.
		}

		//tables for each DI type
		foreach( self::$di_type_tables as $tableDIType => $tableName ){
			self::$prop_tables[$tableName] = new SMWSQLStore3Table( $tableDIType, $tableName );
		}

		//tables for special properties
		foreach( self::$special_tables as $key => $tableName ){
			$typeId = SMWDIProperty::getPredefinedPropertyTypeId( $key );
			$diType = SMWDataValueFactory::getDataItemId( $typeId );
			self::$prop_tables[$tableName] = new SMWSQLStore3Table( $diType, $tableName, $key );
		}
		// why this??
		self::$prop_tables['smw_redi']->idsubject = false;

		//TODO - smw_conc appears in di_type_tables as well special_tables. Fix this

		wfRunHooks( 'SMWPropertyTables', array( &self::$prop_tables ) );

		// TODO - Do we really need this??
		foreach ( self::$prop_tables as $tid => $proptable ) { // fixed property tables are added to known "special" tables
			if ( $proptable->fixedproperty != false ) {
				self::$special_tables[$proptable->fixedproperty] = $tid;
			}
		}

		//get all the tables for the properties that are declared as fixed (overly used and thus having separate tables)
		foreach(self::$fixedProperties as $fixedPropertyLabel => $tableDIType){
			$tableName = self::findFixedPropertyTableID ( $fixedPropertyLabel );
			self::$prop_tables[$tableName] = new SMWSQLStore3Table( $tableDIType, $tableName, $fixedPropertyLabel );
		}

		return self::$prop_tables;
	}

}
