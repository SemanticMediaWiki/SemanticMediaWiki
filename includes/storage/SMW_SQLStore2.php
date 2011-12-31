<?php

/**
 * New SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 *
 * @file
 * @ingroup SMWStore
 */

// The use of the following constants is explained in SMWSQLStore2::setup():
define( 'SMW_SQL2_SMWIW_OUTDATED', ':smw' ); // virtual "interwiki prefix" for old-style special SMW objects (no longer used)
define( 'SMW_SQL2_SMWREDIIW', ':smw-redi' ); // virtual "interwiki prefix" for SMW objects that are redirected
define( 'SMW_SQL2_SMWBORDERIW', ':smw-border' ); // virtual "interwiki prefix" separating very important pre-defined properties from the rest
define( 'SMW_SQL2_SMWPREDEFIW', ':smw-preprop' ); // virtual "interwiki prefix" marking predefined objects (non-movable)
define( 'SMW_SQL2_SMWINTDEFIW', ':smw-intprop' ); // virtual "interwiki prefix" marking internal (invisible) predefined properties

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
class SMWSQLStore2 extends SMWStore {

	/// Cache for SMW IDs
	protected $m_idCache;

	/// Cache for SMWSemanticData objects, indexed by SMW ID
	protected $m_semdata = array();
	/// Like SMWSQLStore2::m_semdata, but containing flags indicating completeness of the SMWSemanticData objs
	protected $m_sdstate = array();
	/// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data
	protected static $in_getSemanticData = 0;

	/// Array for keeping property table table data, indexed by table id.
	/// Access this only by calling getPropertyTables().
	protected static $prop_tables = array();
	/// Array to cache "propkey => table id" associations for fixed property tables. Built only when needed.
	protected static $fixed_prop_tables = null;

	/// Use pre-defined ids for Very Important Properties, avoiding frequent ID lookups for those
	protected static $special_ids = array(
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
	);

	/// Use special tables for Very Important Properties
	protected static $special_tables = array(
		'_TYPE' => 'smw_spec2',
		'_URI'  => 'smw_spec2',
		'_INST' => 'smw_inst2',
		'_UNIT' => 'smw_spec2',
		'_IMPO' => 'smw_spec2',
		'_CONV' => 'smw_spec2',
		'_SERV' => 'smw_spec2',
		'_PVAL' => 'smw_spec2',
		'_REDI' => 'smw_redi2',
		'_SUBP' => 'smw_subp2',
		'_SUBC' => 'smw_subs2',
		'_CONC' => 'smw_conc2',
		'_SF_DF' => 'smw_spec2', // Semantic Form's default form property
		'_SF_AF' => 'smw_spec2',  // Semantic Form's alternate form property
		//'_ERRP', '_MDAT', '_CDAT', '_SKEY' // no special table
		'_LIST' => 'smw_spec2',
	);

	/// Default tables to use for storing data of certain types.
	protected static $di_type_tables = array(
		SMWDataItem::TYPE_NUMBER     => 'smw_atts2',
		SMWDataItem::TYPE_STRING     => 'smw_atts2',
		SMWDataItem::TYPE_BLOB       => 'smw_text2',
		SMWDataItem::TYPE_BOOLEAN    => 'smw_atts2',
		SMWDataItem::TYPE_URI        => 'smw_atts2',
		SMWDataItem::TYPE_TIME       => 'smw_atts2',
		SMWDataItem::TYPE_GEO        => 'smw_coords', // currently created only if Semantic Maps are installed
		SMWDataItem::TYPE_CONTAINER  => 'smw_rels2', // values of this type represented by internal objects, stored like pages in smw_rels2
		SMWDataItem::TYPE_WIKIPAGE   => 'smw_rels2',
		SMWDataItem::TYPE_CONCEPT    => 'smw_conc2', // unlikely to occur as value of a normal property
		SMWDataItem::TYPE_PROPERTY   => 'smw_atts2'  // unlikely to occur as value of any property
	);

	public function __construct() {
		$this->m_idCache = new SMWSqlStore2IdCache( wfGetDB( DB_SLAVE ) );
	}

///// Reading methods /////

	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		wfProfileIn( "SMWSQLStore2::getSemanticData (SMW)" );

		// Do not clear the cache when called recursively.
		self::$in_getSemanticData++;

		// *** Find out if this subject exists ***//
		$sortkey = '';
		$sid = $this->getSMWPageIDandSort( $subject->getDBkey(), $subject->getNamespace(),
			$subject->getInterwiki(), $subject->getSubobjectName(), $sortkey, true );
		if ( $sid == 0 ) { // no data, safe our time
			/// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
			self::$in_getSemanticData--;
			wfProfileOut( "SMWSQLStore2::getSemanticData (SMW)" );
			return new SMWSemanticData( $subject );
		}

		// *** Prepare the cache ***//
		if ( !array_key_exists( $sid, $this->m_semdata ) ) { // new cache entry
			$this->m_semdata[$sid] = new SMWSqlStubSemanticData( $subject, false );
			if ( $subject->getSubobjectName() === '' ) { // no sortkey for subobjects
				$this->m_semdata[$sid]->addPropertyStubValue( '_SKEY', array( $sortkey ) );
			}
			$this->m_sdstate[$sid] = array();
			// Note: the sortkey is always set but belongs to no property table,
			// hence no entry in $this->m_sdstate[$sid] is made.
		}

		if ( ( count( $this->m_semdata ) > 20 ) && ( self::$in_getSemanticData == 1 ) ) {
			// prevent memory leak;
			// It is not so easy to find the sweet spot between cache size and performance gains (both memory and time),
			// The value of 20 was chosen by profiling runtimes for large inline queries and heavily annotated pages.
			$this->m_semdata = array( $sid => $this->m_semdata[$sid] );
			$this->m_sdstate = array( $sid => $this->m_sdstate[$sid] );
		}

		// *** Read the data ***//
		foreach ( self::getPropertyTables() as $tid => $proptable ) {
			if ( array_key_exists( $tid, $this->m_sdstate[$sid] ) ) continue;

			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeid ) {
					$relevant = $relevant || self::tableFitsType( $tid, $typeid );
				}
				if ( !$relevant ) continue;
			}

			$data = $this->fetchSemanticData( $sid, $subject, $proptable );

			foreach ( $data as $d ) {
				$this->m_semdata[$sid]->addPropertyStubValue( reset( $d ), end( $d ) );
			}

			$this->m_sdstate[$sid][$tid] = true;
		}

		self::$in_getSemanticData--;

		wfProfileOut( "SMWSQLStore2::getSemanticData (SMW)" );

		return $this->m_semdata[$sid];
	}

	/**
	 * @see SMWStore::getPropertyValues
	 *
	 * @param $subject mixed SMWDIWikiPage or null
	 * @param $property SMWDIProperty
	 * @param $requestoptions SMWRequestOptions
	 *
	 * @return array of SMWDataItem
	 */
	public function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore2::getPropertyValues (SMW)" );

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMWDIProperty( $property->getKey(), false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestoptions );
		} elseif ( !is_null( $subject ) ) { // subject given, use semantic data cache
			$sd = $this->getSemanticData( $subject, array( $property->findPropertyTypeID() ) );
			$result = $this->applyRequestOptions( $sd->getPropertyValues( $property ), $requestoptions );
		} else { // no subject given, get all values for the given property
			$pid = $this->getSMWPropertyID( $property );
			$tableid = self::findPropertyTableID( $property );

			if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
				wfProfileOut( "SMWSQLStore2::getPropertyValues (SMW)" );
				return array();
			}

			$proptables = self::getPropertyTables();
			$data = $this->fetchSemanticData( $pid, $property, $proptables[$tableid], false, $requestoptions );
			$result = array();
			$propertyTypeId = $property->findPropertyTypeID();
			$propertyDiId = SMWDataValueFactory::getDataItemId( $propertyTypeId );

			if ( $propertyDiId == SMWDataItem::TYPE_CONTAINER ) {
				foreach ( $data as $dbkeys ) {
					try {
						$diSubWikiPage = SMWCompatibilityHelpers::dataItemFromDBKeys( '_wpg', $dbkeys );
						$semanticData = new SMWContainerSemanticData( $diSubWikiPage );
						$semanticData->copyDataFrom( $this->getSemanticData( $diSubWikiPage ) );
						$result[] = new SMWDIContainer( $semanticData );
					} catch ( SMWDataItemException $e ) {
						// maybe type assignment changed since data was stored;
						// don't worry, but we can only drop the data here
					}
				}
			} else {
				foreach ( $data as $dbkeys ) {
					try {
						$result[] = SMWCompatibilityHelpers::dataItemFromDBKeys( $propertyTypeId, $dbkeys );
					} catch ( SMWDataItemException $e ) {
						// maybe type assignment changed since data was stored;
						// don't worry, but we can only drop the data here
					}
				}
			}
		}

		wfProfileOut( "SMWSQLStore2::getPropertyValues (SMW)" );

		return $result;
	}

	/**
	 * Helper function for reading all data for from a given property table (specified by an
	 * SMWSQLStore2Table object), based on certain restrictions. The function can filter data
	 * based on the subject (1) or on the property it belongs to (2) -- but one of those must
	 * be done. The Boolean $issubject is true for (1) and false for (2).
	 *
	 * In case (1), the first two parameters are taken to refer to a subject; in case (2) they
	 * are taken to refer to a property. In any case, the retrieval is limited to the specified
	 * $proptable. The parameters are an internal $id (of a subject or property), and an $object
	 * (being an SMWDIWikiPage or SMWDIProperty). Moreover, when filtering by property, it is
	 * assumed that the given $proptable belongs to the property: if it is a table with fixed
	 * property, it will not be checked that this is the same property as the one that was given
	 * in $object.
	 *
	 * In case (1), the result in general is an array of pairs (arrays of size 2) consisting of
	 * a property name (string), and an array of DB keys (array) from which a datvalue object for
	 * this value could be built. It is possible that some of the DB keys are based on internal
	 * objects; these will be represented by similar result arrays of (recursive calls of)
	 * fetchSemanticData().
	 *
	 * In case (2), the result is simply an array of DB keys (array) without the property strings.
	 * Container objects will be encoded with nested arrays like in case (1).
	 *
	 * @todo Maybe share DB handler; asking for it seems to take quite some time and we do not want
	 * to change it in one call.
	 *
	 * @param integer $id
	 * @param SMWDataItem $object
	 * @param SMWSQLStore2Table $proptable
	 * @param boolean $issubject
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array
	 */
	protected function fetchSemanticData( $id, $object, $proptable, $issubject = true, $requestoptions = null ) {
		// stop if there is not enough data:
		// properties always need to be given as object, subjects at least if !$proptable->idsubject
		if ( ( $id == 0 ) || ( is_null( $object ) && ( !$issubject || !$proptable->idsubject ) ) ) return array();

		wfProfileIn( "SMWSQLStore2::fetchSemanticData-" . $proptable->name .  " (SMW)" );
		$result = array();
		$db = wfGetDB( DB_SLAVE );

		// ***  First build $from, $select, and $where for the DB query  ***//
		$from   = $db->tableName( $proptable->name ); // always use actual table
		$select = '';
		$where  = '';

		if ( $issubject != 0 ) { // restrict subject, select property
			$where .= ( $proptable->idsubject ) ? 's_id=' . $db->addQuotes( $id ) :
					  's_title=' . $db->addQuotes( $object->getDBkey() ) .
					  ' AND s_namespace=' . $db->addQuotes( $object->getNamespace() );
			if ( !$proptable->fixedproperty && !$proptable->specpropsonly ) { // get property name
				$from .= ' INNER JOIN ' . $db->tableName( 'smw_ids' ) . ' AS p ON p_id=p.smw_id';
				$select .= 'p.smw_title as prop';
			} elseif ( $proptable->specpropsonly ) { // avoid join for tables that contain only built-in properties
				$select .= 'p_id';
			} // else: fixed property, no select needed at all to get at it
		} elseif ( !$proptable->fixedproperty ) { // restrict property, but don't select subject
			$where .= 'p_id=' . $db->addQuotes( $id );
		}

		$valuecount = 0;
		$usedistinct = true; // use DISTINCT option only if no text blobs are among values
		$selectvalues = array(); // array for all values to be selected, kept to help finding value and label fields below

		foreach ( $proptable->objectfields as $fieldname => $typeid ) { // now add select entries for object column(s)
			if ( $typeid == 'p' ) { // Special case: page id, use smw_id table to insert 4 page-specific values instead of internal id
				$from .= ' INNER JOIN ' . $db->tableName( 'smw_ids' ) . " AS o$valuecount ON $fieldname=o$valuecount.smw_id";
				$select .= ( ( $select !== '' ) ? ',' : '' ) . "$fieldname AS id$valuecount";

				$selectvalues[$valuecount] = "o$valuecount.smw_title";
				$selectvalues[$valuecount + 1] = "o$valuecount.smw_namespace";
				$selectvalues[$valuecount + 2] = "o$valuecount.smw_iw";
				$selectvalues[$valuecount + 3] = "o$valuecount.smw_sortkey";
				$selectvalues[$valuecount + 4] = "o$valuecount.smw_subobject";

				$valuecount += 4;
			} else { // Just use value as given.
				$selectvalues[$valuecount] = $fieldname;
			}

			if ( $typeid == 'l' ) $usedistinct = false;
			$valuecount += 1;
		}

		foreach ( $selectvalues as $index => $field ) {
			$select .= ( ( $select !== '' ) ? ',' : '' ) . "$field AS v$index";
		}

		if ( !$issubject ) { // Needed to apply sorting/string matching in query; only with fixed property.
			list( $sig, $valueIndex, $labelIndex ) = self::getTypeSignature( $object->findPropertyTypeID() );
			$valuecolumn = ( array_key_exists( $valueIndex, $selectvalues ) ) ? $selectvalues[$valueIndex] : '';
			$labelcolumn = ( array_key_exists( $labelIndex, $selectvalues ) ) ? $selectvalues[$labelIndex] : '';
			$where .= $this->getSQLConditions( $requestoptions, $valuecolumn, $labelcolumn, $where !== '' );
		} else {
			$valuecolumn = $labelcolumn = '';
		}

		// ***  Now execute the query and read the results  ***//
		$res = $db->select( $from, $select, $where, 'SMW::getSemanticData',
		       ( $usedistinct ? $this->getSQLOptions( $requestoptions, $valuecolumn ) + array( 'DISTINCT' ) :
		                        $this->getSQLOptions( $requestoptions, $valuecolumn ) ) );

		foreach ( $res as $row ) {
			if ( $issubject && !$proptable->fixedproperty ) { // use joined or predefined property name
				if ( $proptable->specpropsonly ) {
					$propertyname = array_search( $row->p_id, self::$special_ids );
					// Note: this may leave $propertyname false if a special type
					// has been assigned to a proerty not in self::$special_ids.
					// Extensions could do this, but this will not work.
					if ( $propertyname == false ) continue;
				} else {
					$propertyname = $row->prop;
				}
			} elseif ( $issubject ) { // use fixed property name
				$propertyname = $proptable->fixedproperty;
			}

			$valuekeys = array();
			for ( $i = 0; $i < $valuecount; $i += 1 ) { // read the value fields from the current row
				$fieldname = "v$i";
				$valuekeys[] = $row->$fieldname;
			}

			// Filter out any accidentally retrieved internal things (interwiki starts with ":"):
			if ( $proptable->getFieldSignature() != 'p' || count( $valuekeys ) < 3 ||
			     $valuekeys[2] === '' ||  $valuekeys[2]{0} != ':' ) {
				$result[] = $issubject ? array( $propertyname, $valuekeys ) : $valuekeys;
			}
		}

		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStore2::fetchSemanticData-" . $proptable->name .  " (SMW)" );

		return $result;
	}

	/**
	 * @see SMWStore::getPropertySubjects
	 *
	 * @param SMWDIProperty $property
	 * @param mixed $value SMWDataItem or null
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of SMWDIWikiPage
	 */
	public function getPropertySubjects( SMWDIProperty $property, $value, $requestoptions = null ) {
		/// TODO: should we share code with #ask query computation here? Just use queries?
		wfProfileIn( "SMWSQLStore2::getPropertySubjects (SMW)" );

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMWDIProperty( $property->getKey(), false );
			$result = $this->getPropertyValues( $value, $noninverse, $requestoptions );
			wfProfileOut( "SMWSQLStore2::getPropertySubjects (SMW)" );
			return $result;
		}

		// First build $select, $from, and $where for the DB query
		$where = $from = '';
		$pid = $this->getSMWPropertyID( $property );
		$tableid = self::findPropertyTableID( $property );

		if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
			wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
			return array();
		}

		$proptables = self::getPropertyTables();
		$proptable = $proptables[$tableid];
		$db = wfGetDB( DB_SLAVE );

		if ( $proptable->idsubject ) { // join in smw_ids to get title data
			$from = $db->tableName( 'smw_ids' ) . " INNER JOIN " . $db->tableName( $proptable->name ) . " AS t1 ON t1.s_id=smw_id";
			$select = 'smw_title, smw_namespace, smw_sortkey, smw_iw, smw_subobject';
		} else { // no join needed, title+namespace as given in proptable
			$from = $db->tableName( $proptable->name ) . " AS t1";
			$select = 's_title AS smw_title, s_namespace AS smw_namespace, s_title AS smw_sortkey, \'\' AS smw_iw, \'\' AS smw_subobject';
		}

		if ( $proptable->fixedproperty == false ) {
			$where .= ( $where ? ' AND ' : '' ) . "t1.p_id=" . $db->addQuotes( $pid );
		}

		$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

		// ***  Now execute the query and read the results  ***//
		$result = array();
		$res = $db->select( $from, 'DISTINCT ' . $select,
		                    $where . $this->getSQLConditions( $requestoptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
		                    'SMW::getPropertySubjects',
		                    $this->getSQLOptions( $requestoptions, 'smw_sortkey' ) );

		foreach ( $res as $row ) {
			try {
				if ( $row->smw_iw === '' || $row->smw_iw{0} != ':' ) { // filter special objects
					$result[] = new SMWDIWikiPage( $row->smw_title, $row->smw_namespace, $row->smw_iw, $row->smw_subobject );
				}
			} catch ( SMWDataItemException $e ) {
				// silently drop data, should be extremely rare and will usually fix itself at next edit
			}
		}

		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStore2::getPropertySubjects (SMW)" );

		return $result;
	}


	/**
	 * Helper function to compute from and where strings for a DB query so that
	 * only rows of the given value object match. The parameter $tableindex
	 * counts that tables used in the query to avoid duplicate table names. The
	 * parameter $proptable provides the SMWSQLStore2Table object that is
	 * queried.
	 *
	 * @todo Maybe do something about redirects. The old code was
	 * $oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki(),false);
	 *
	 * @param string $from
	 * @param string $where
	 * @param SMWSQLStore2Table $proptable
	 * @param SMWDataItem $value
	 * @param integer $tableindex
	 */
	protected function prepareValueQuery( &$from, &$where, $proptable, $value, $tableindex = 1 ) {
		$db = wfGetDB( DB_SLAVE );

		if ( $value instanceof SMWDIContainer ) { // recursive handling of containers
			$keys = array_keys( $proptable->objectfields );
			$joinfield = "t$tableindex." . reset( $keys ); // this must be a type 'p' object
			$proptables = self::getPropertyTables();
			$semanticData = $value->getSemanticData();

			foreach ( $semanticData->getProperties() as $subproperty ) {
				$tableid = self::findPropertyTableID( $subproperty );
				$subproptable = $proptables[$tableid];

				foreach ( $semanticData->getPropertyValues( $subproperty ) as $subvalue ) {
					$tableindex++;

					if ( $subproptable->idsubject ) { // simply add property table to check values
						$from .= " INNER JOIN " . $db->tableName( $subproptable->name ) . " AS t$tableindex ON t$tableindex.s_id=$joinfield";
					} else { // exotic case with table that uses subject title+namespace in container object (should never happen in SMW core)
						$from .= " INNER JOIN " . $db->tableName( 'smw_ids' ) . " AS ids$tableindex ON ids$tableindex.smw_id=$joinfield" .
						         " INNER JOIN " . $db->tableName( $subproptable->name ) . " AS t$tableindex ON " .
						         "t$tableindex.s_title=ids$tableindex.smw_title AND t$tableindex.s_namespace=ids$tableindex.smw_namespace";
					}

					if ( $subproptable->fixedproperty == false ) { // the ID we get should be !=0, so no point in filtering the converse
						$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.p_id=" . $db->addQuotes( $this->getSMWPropertyID( $subproperty ) );
					}

					$this->prepareValueQuery( $from, $where, $subproptable, $subvalue, $tableindex );
				}
			}
		} elseif ( !is_null( $value ) ) { // add conditions for given value
			/// TODO This code still partly supports some abandoned flexibility of the DBkeys system;
			/// this is not very clean (see break; below) and should be improved
			$dbkeys = SMWCompatibilityHelpers::getDBkeysFromDataItem( $value );
			$i = 0;

			foreach ( $proptable->objectfields as $fieldname => $typeid ) {
				if ( $i >= count( $dbkeys ) ) break;

				if ( $typeid == 'p' ) { // Special case: page id, resolve this in advance
					$oid = $this->getSMWPageID( $value->getDBkey(), $value->getNamespace(), $value->getInterwiki(), $value->getSubobjectName() );
					$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.$fieldname=" . $db->addQuotes( $oid );
					break;
				} elseif ( $typeid != 'l' ) { // plain value, but not a text blob
					$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.$fieldname=" . $db->addQuotes( $dbkeys[$i] );
				}

				$i += 1;
			}
		}
	}

	/**
	 * @see SMWStore::getAllPropertySubjects
	 * 
	 * @param SMWDIProperty $property
	 * @param SMWRequestOptions $requestoptions
	 * 
	 * @return array of SMWDIWikiPage
	 */
	public function getAllPropertySubjects( SMWDIProperty $property, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore2::getAllPropertySubjects (SMW)" );
		$result = $this->getPropertySubjects( $property, null, $requestoptions );
		wfProfileOut( "SMWSQLStore2::getAllPropertySubjects (SMW)" );

		return $result;
	}

	/**
	 * @see SMWStore::getProperties
	 *
	 * @param SMWDIWikiPage $subject
	 * @param SMWRequestOptions $requestoptions
	 */
	public function getProperties( SMWDIWikiPage $subject, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore2::getProperties (SMW)" );
		$sid = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName() );

		if ( $sid == 0 ) { // no id, no page, no properties
			wfProfileOut( "SMWSQLStore2::getProperties (SMW)" );
			return array();
		}

		$db = wfGetDB( DB_SLAVE );
		$result = array();

		if ( $requestoptions !== null ) { // potentially need to get more results, since options apply to union
			$suboptions = clone $requestoptions;
			$suboptions->limit = $requestoptions->limit + $requestoptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}

		foreach ( self::getPropertyTables() as $proptable ) {
			$from = $db->tableName( $proptable->name );

			if ( $proptable->idsubject ) {
				$where = 's_id=' . $db->addQuotes( $sid );
			} elseif ( $subject->getInterwiki() === '' ) {
				$where = 's_title=' . $db->addQuotes( $subject->getDBkey() ) . ' AND s_namespace=' . $db->addQuotes( $subject->getNamespace() );
			} else { // subjects with non-emtpy interwiki cannot have properties
				continue;
			}

			if ( $proptable->fixedproperty == false ) { // select all properties
				$from .= " INNER JOIN " . $db->tableName( 'smw_ids' ) . " ON smw_id=p_id";
				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey',
					// (select sortkey since it might be used in ordering (needed by Postgres))
					$where . $this->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey' ),
					'SMW::getProperties', $this->getSQLOptions( $suboptions, 'smw_sortkey' ) );

				foreach ( $res as $row ) {
					$result[] = new SMWDIProperty( $row->smw_title );
				}
			} else { // just check if subject occurs in table
				$res = $db->select( $from, '*', $where, 'SMW::getProperties', array( 'LIMIT' => 1 ) );

				if ( $db->numRows( $res ) > 0 ) {
					$result[] = new SMWDIProperty( $proptable->fixedproperty );
				}
			}

			$db->freeResult( $res );
		}

		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStore2::getProperties (SMW)" );

		return $result;
	}

	/**
	 * Implementation of SMWStore::getInProperties(). This function is meant to
	 * be used for finding properties that link to wiki pages.
	 * 
	 * @see SMWStore::getInProperties
	 * 
	 * TODO: When used for other datatypes, the function may return too many
	 * properties since it selects results by comparing the stored information
	 * (DB keys) only, while not currently comparing the type of the returned
	 * property to the type of the queried data. So values with the same DB keys
	 * can be confused. This is a minor issue now since no code is known to use
	 * this function in cases where this occurs.
	 *
	 * @param SMWDataItem $value
	 * @param SMWRequestOptions $requestoptions
	 * 
	 * @return array of SMWWikiPageValue
	 */
	public function getInProperties( SMWDataItem $value, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore2::getInProperties (SMW)" );

		$db = wfGetDB( DB_SLAVE );
		$result = array();

		// Potentially need to get more results, since options apply to union.
		if ( $requestoptions !== null ) {
			$suboptions = clone $requestoptions;
			$suboptions->limit = $requestoptions->limit + $requestoptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}

		$tableIds = self::findAllDiTypeTableIds( $value->getDIType() );
		$proptables = self::getPropertyTables();
		foreach ( $tableIds as $tid ) {
			$proptable = $proptables[$tid];
			$where = $from = '';
			if ( $proptable->fixedproperty == false ) { // join smw_ids to get property titles
				$from = $db->tableName( 'smw_ids' ) . " INNER JOIN " . $db->tableName( $proptable->name ) . " AS t1 ON t1.p_id=smw_id";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey',
						// select sortkey since it might be used in ordering (needed by Postgres)
						$where . $this->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
						'SMW::getInProperties', $this->getSQLOptions( $suboptions, 'smw_sortkey' ) );

				foreach ( $res as $row ) {
					try {
						$result[] = new SMWDIProperty( $row->smw_title );
					} catch (SMWDataItemException $e) {
						// has been observed to happen (empty property title); cause unclear; ignore this data
					}
				}
			} else {
				$from = $db->tableName( $proptable->name ) . " AS t1";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );
				$res = $db->select( $from, '*', $where, 'SMW::getInProperties', array( 'LIMIT' => 1 ) );

				if ( $db->numRows( $res ) > 0 ) {
					$result[] = new SMWDIProperty( $proptable->fixedproperty );
				}
			}
			$db->freeResult( $res );
		}

		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStore2::getInProperties (SMW)" );

		return $result;
	}

///// Writing methods /////

	/**
	 * @see SMWStore::deleteSubject
	 * 
	 * @param Title $subject
	 */
	public function deleteSubject( Title $subject ) {
		wfProfileIn( 'SMWSQLStore2::deleteSubject (SMW)' );
		wfRunHooks( 'SMWSQLStore2::deleteSubjectBefore', array( $this, $subject ) );

		$this->deleteSemanticData( SMWDIWikiPage::newFromTitle( $subject ) );
		$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() ); // also delete redirects, may trigger update jobs!

		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = wfGetDB( DB_MASTER );
			$id = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );
			$db->delete( 'smw_conc2', array( 's_id' => $id ), 'SMW::deleteSubject::Conc2' );
			$db->delete( 'smw_conccache', array( 'o_id' => $id ), 'SMW::deleteSubject::Conccache' );
		}

		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
		///FIXME: clean internal caches here
		wfRunHooks( 'SMWSQLStore2::deleteSubjectAfter', array( $this, $subject ) );
		wfProfileOut( 'SMWSQLStore2::deleteSubject (SMW)' );
	}

	/**
	 * @see SMWStore::doDataUpdate
	 * 
	 * @param SMWSemanticData $data
	 */
	public function doDataUpdate( SMWSemanticData $data ) {
		wfProfileIn( "SMWSQLStore2::updateData (SMW)" );
		wfRunHooks( 'SMWSQLStore2::updateDataBefore', array( $this, $data ) );
		
		$subject = $data->getSubject();
		$this->deleteSemanticData( $subject );

		$redirects = $data->getPropertyValues( new SMWDIProperty( '_REDI' ) );
		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace(), $redirect->getDBkey(), $redirect->getNameSpace() );
			wfProfileOut( "SMWSQLStore2::updateData (SMW)" );
			return; // Stop here -- no support for annotations on redirect pages!
		} else {
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() );
		}

		$sortkeyDataItems = $data->getPropertyValues( new SMWDIProperty( '_SKEY' ) );
		$sortkeyDataItem = end( $sortkeyDataItems );
		if ( $sortkeyDataItem instanceof SMWDIString ) {
			$sortkey = $sortkeyDataItem->getString();
		} else { // default sortkey
			$sortkey = str_replace( '_', ' ', $subject->getDBkey() );
		}

		// Always make an ID (pages without ID cannot be in query results, not even in fixed value queries!):
		$sid = $this->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName(), true, $sortkey );
		$updates = array(); // collect data for bulk updates; format: tableid => updatearray
		$this->prepareDBUpdates( $updates, $data, $sid, $subject );

		$db = wfGetDB( DB_MASTER );
		foreach ( $updates as $tablename => $uvals ) {
			if ( $tablename != 'smw_conc2' ) $db->insert( $tablename, $uvals, "SMW::updateData$tablename" );
		}

		// Concepts are not just written but carefully updated,
		// preserving existing metadata (cache ...) for a concept:
		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) {
			if ( array_key_exists( 'smw_conc2', $updates ) && ( count( $updates['smw_conc2'] ) != 0 ) ) {
				$up_conc2 = end( $updates['smw_conc2'] );
				unset ( $up_conc2['cache_date'] );
				unset ( $up_conc2['cache_count'] ); 
			} else {
				$up_conc2 = array(
				     'concept_txt'   => '',
				     'concept_docu'  => '',
				     'concept_features' => 0,
				     'concept_size'  => -1,
				     'concept_depth' => -1
				);
			}

			$row = $db->selectRow(
				'smw_conc2',
				array( 'cache_date', 'cache_count' ),
				array( 's_id' => $sid ),
				'SMWSQLStore2Queries::updateConst2Data'
			);

			if ( ( $row === false ) && ( $up_conc2['concept_txt'] !== '' ) ) { // insert newly given data
				$up_conc2['s_id'] = $sid;
				$db->insert( 'smw_conc2', $up_conc2, 'SMW::updateConc2Data' );
			} elseif ( $row !== false ) { // update data, preserve existing entries
				$db->update( 'smw_conc2', $up_conc2, array( 's_id' => $sid ), 'SMW::updateConc2Data' );
			}
		}

		// Finally update caches (may be important if jobs are directly following this call)
		$this->m_semdata[$sid] = SMWSqlStubSemanticData::newFromSemanticData( $data );
		// Everything that one can know.
		$this->m_sdstate[$sid] = array();
		foreach ( self::getPropertyTables() as $tableId => $tableDeclaration ) {
			$this->m_sdstate[$sid][$tableId] = true;
		}
		
		wfRunHooks( 'SMWSQLStore2::updateDataAfter', array( $this, $data ) );
		
		wfProfileOut( "SMWSQLStore2::updateData (SMW)" );
	}

	/**
	 * Extend the given update array to account for the data in the
	 * SMWSemanticData object. The subject page of the data container is
	 * ignored, and the given $sid (subject page id) is used directly. If
	 * this ID is 0, then $subject is used to find an ID. This is usually
	 * the case for all internal objects that are created in writing
	 * container values.
	 *
	 * The function returns the id that was used for writing. Especially,
	 * any newly created internal id is returned.
	 *
	 * @param $updates array
	 * @param $data SMWSemanticData
	 * @param $sid integer pre-computed id if available or 0 if ID should be sought
	 * @param $subject SMWDIWikiPage subject to which the data refers
	 */
	protected function prepareDBUpdates( &$updates, SMWSemanticData $data, $sid, SMWDIWikiPage $subject ) {
		if ( $sid == 0 ) {
			$sid = $this->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(),
				$subject->getInterwiki(), $subject->getSubobjectName(), true,
				str_replace( '_', ' ', $subject->getDBkey() ) . $subject->getSubobjectName() );
		}

		$proptables = self::getPropertyTables();

		foreach ( $data->getProperties() as $property ) {
			if ( ( $property->getKey() == '_SKEY' ) || ( $property->getKey() == '_REDI' ) ) {
				continue; // skip these here, we store them differently
			}

			$tableid = self::findPropertyTableID( $property );
			$proptable = $proptables[$tableid];

			foreach ( $data->getPropertyValues( $property ) as $di ) {
				if ( $di instanceof SMWDIError ) { // error values, ignore
					continue;
				}
				// redirects were treated above

				///TODO check needed if subject is null (would happen if a user defined proptable with !idsubject was used on an internal object -- currently this is not possible
				$uvals = $proptable->idsubject ? array( 's_id' => $sid ) :
				         array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() );
				if ( $proptable->fixedproperty == false ) {
					$uvals['p_id'] = $this->makeSMWPropertyID( $property );
				}

				if ( $di instanceof SMWDIContainer ) { // process subobjects recursively
					$subObject = $di->getSemanticData()->getSubject();
					$subObjectId = $this->prepareDBUpdates( $updates, $di->getSemanticData(), 0, $subObject );
					// Note: tables for container objects MUST have objectfields == array(<somename> => 'p')
					reset( $proptable->objectfields );
					$uvals[key( $proptable->objectfields )] = $subObjectId;
				} else {
					$dbkeys = SMWCompatibilityHelpers::getDBkeysFromDataItem( $di );
					reset( $dbkeys );

					foreach ( $proptable->objectfields as $fieldname => $typeid ) {
						if ( $typeid != 'p' ) {
							$uvals[$fieldname] = current( $dbkeys );
						} else {
							$uvals[$fieldname] = $this->makeSMWPageID( $di->getDBkey(), $di->getNamespace(), $di->getInterwiki(), $di->getSubobjectName() );
						}

						next( $dbkeys );
					}
				}

				if ( !array_key_exists( $proptable->name, $updates ) ) {
					$updates[$proptable->name] = array();
				}

				$updates[$proptable->name][] = $uvals;
			}
		}

		return $sid;
	}

	/**
	 * Implementation of SMWStore::changeTitle(). In contrast to
	 * updateRedirects(), this function does not simply write a redirect
	 * from the old page to the new one, but also deletes all data that may
	 * already be stored for the new title (normally the new title should
	 * belong to an empty page that has no data but at least it could have a
	 * redirect to the old page), and moves all data that exists for the old
	 * title to the new location. Thus, the function executes three steps:
	 * delete data at newtitle, move data from oldtitle to newtitle, and set
	 * redirect from oldtitle to newtitle. In some cases, the goal can be
	 * achieved more efficiently, e.g. if the new title does not occur in SMW
	 * yet: then we can just change the ID records for the titles instead of
	 * changing all data tables
	 *
	 * Note that the implementation ignores the MediaWiki IDs since this
	 * store has its own ID management. Also, the function requires that both
	 * titles are local, i.e. have empty interwiki prefix.
	 *
	 * TODO: Currently the sortkey is not moved with the remaining data. It is
	 * not possible to move it reliably in all cases: we cannot distinguish an
	 * unset sortkey from one that was set to the name of oldtitle. Maybe use
	 * update jobs right away?
	 *
	 * @param Title $oldtitle
	 * @param Title $newtitle
	 * @param integer $pageid
	 * @param integer $redirid
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		global $smwgQEqualitySupport;
		wfProfileIn( "SMWSQLStore2::changeTitle (SMW)" );

		// get IDs but do not resolve redirects:
		$sid = $this->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', false );
		$tid = $this->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '', false );
		$db = wfGetDB( DB_MASTER );

		// Easy case: target not used anywhere yet, just hijack its title for our current id
		if ( ( $tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
			// This condition may not hold even if $newtitle is
			// currently unused/non-existing since we keep old IDs.
			// If equality support is off, then this simple move
			// does too much; fall back to general case below.
			if ( $sid != 0 ) { // change id entry to refer to the new title
				// Note that this also changes the reference for internal objects (subobjects)
				$db->update( 'smw_ids', array( 'smw_title' => $newtitle->getDBkey(),
					'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
					array( 'smw_title' => $oldtitle->getDBkey(),
					'smw_namespace' => $oldtitle->getNamespace(), 'smw_iw' => '' ),
					__METHOD__ );
				$this->m_idCache->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(),
					$newtitle->getDBkey(), $newtitle->getNamespace() );
				$this->m_idCache->setId( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', 0 );
				$this->m_idCache->setId( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '', $sid );
			} else { // make new (target) id for use in redirect table
				$sid = $this->makeSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '' );
			} // at this point, $sid is the id of the target page (according to smw_ids)

			// make redirect id for oldtitle:
			$this->makeSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), SMW_SQL2_SMWREDIIW, '' ); 
			$db->insert( 'smw_redi2', array( 's_title' => $oldtitle->getDBkey(),
						's_namespace' => $oldtitle->getNamespace(),
						'o_id' => $sid ),
			             __METHOD__ );

			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behaviour: everything will continue to work until the existing redirects are updated,
			/// which will hopefully be done to fix the double redirect.
		} else { // General move method: should always be correct
			// (equality support respected when updating redirects)

			// Delete any existing data from new title:
			// $newtitle should not have data, but let's be sure
			$this->deleteSemanticData( SMWDIWikiPage::newFromTitle( $newtitle ) );
			// Update (i.e. delete) redirects (may trigger update jobs):
			$this->updateRedirects( $newtitle->getDBkey(), $newtitle->getNamespace() );

			// Move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->changeSMWPageID( $sid, $tid, $oldtitle->getNamespace(),
					$newtitle->getNamespace(), true, false );
			}

			// Associate internal objects (subobjects) with the new title:
			$table = $db->tableName( 'smw_ids' );
			$values = array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' );
			$sql = "UPDATE $table SET " . $db->makeList( $values, LIST_SET ) .
				' WHERE smw_title = ' . $db->addQuotes( $oldtitle->getDBkey() ) . ' AND ' .
				'smw_namespace = ' . $db->addQuotes( $oldtitle->getNamespace() ) . ' AND ' .
				'smw_iw = ' . $db->addQuotes( '' ) . ' AND ' .
				'smw_subobject != ' . $db->addQuotes( '' );
			$db->query( $sql, __METHOD__ );
// The below code can be used instead when moving to MW 1.17 (support for '!' in Database::makeList()):
// 			$db->update( 'smw_ids', 
// 				array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
// 				array( 'smw_title' => $oldtitle->getDBkey(), 'smw_namespace' => $oldtitle->getNamespace(), 'smw_iw' => '', 'smw_subobject!' => array( '' ) ), // array() needed for ! to work
// 				__METHOD__ );
			$this->m_idCache->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(),
				$newtitle->getDBkey(), $newtitle->getNamespace() );

			// Write a redirect from old title to new one:
			// (this also updates references in other tables as needed.)
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
			$this->updateRedirects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );
		}
		
		wfProfileOut( "SMWSQLStore2::changeTitle (SMW)" );
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
		wfProfileIn( 'SMWSQLStore2::getQueryResult (SMW)' );
		global $smwgIP;
		include_once( "$smwgIP/includes/storage/SMW_SQLStore2_Queries.php" );

		$qe = new SMWSQLStore2QueryEngine( $this, wfGetDB( DB_SLAVE ) );
		$result = $qe->getQueryResult( $query );
		wfProfileOut( 'SMWSQLStore2::getQueryResult (SMW)' );

		return $result;
	}

///// Special page functions /////

	/**
	 * @todo Properties that are stored in dedicated tables
	 * (SMWSQLStore2Table::fixedproperty) are currently ignored.
	 */
	public function getPropertiesSpecial( $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore2::getPropertiesSpecial (SMW)" );
		$db = wfGetDB( DB_SLAVE );
		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$queries = array();

		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $proptable->fixedproperty == false ) {
				$queries[] = 'SELECT smw_id, smw_title, COUNT(*) as count, smw_sortkey FROM ' .
					$db->tableName( $proptable->name ) . ' INNER JOIN ' .
					$db->tableName( 'smw_ids' ) . ' ON p_id=smw_id WHERE smw_iw=' .
					$db->addQuotes( '' ) . ' OR smw_iw=' . $db->addQuotes( SMW_SQL2_SMWPREDEFIW ) .
					' GROUP BY smw_id,smw_title,smw_sortkey';
			} // else: properties with special tables are ignored for now; maybe fix in the future
		}

		$query = '(' . implode( ') UNION (', $queries ) . ') ORDER BY smw_sortkey';
		// The following line is possible in MW 1.6 and above only:
		// $query = $db->unionQueries($queries, false) . ' ORDER BY smw_sortkey'; // should probably use $db->makeSelectOptions()
		if ( $requestoptions !== null ) {
			if ( $requestoptions->limit > 0 ) {
				$query = $db->limitResult( $query, $requestoptions->limit, ( $requestoptions->offset > 0 ) ? $requestoptions->offset:0 );
			}
		}

		$res = $db->query( $query, 'SMW::getPropertySubjects' );
		$result = array();

		foreach ( $res as $row ) {
			$result[] = array( new SMWDIProperty( $row->smw_title ), $row->count );
		}

		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStore2::getPropertiesSpecial (SMW)" );

		return $result;
	}

	/**
	 * Implementation of SMWStore::getUnusedPropertiesSpecial(). It works by
	 * creating a temporary table with all property pages from which all used
	 * properties are then deleted. This is still a costy operation, and some
	 * slower but lessdemanding way of getting at this data is required for
	 * larger wikis.
	 */
	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		global $wgDBtype;

		wfProfileIn( "SMWSQLStore2::getUnusedPropertiesSpecial (SMW)" );
		$db = wfGetDB( DB_SLAVE );

		// we use a temporary table for executing this costly operation on the DB side
		$smw_tmp_unusedprops = $db->tableName( 'smw_tmp_unusedprops' );
		if ( $wgDBtype == 'postgres' ) { // PostgresQL: no in-memory tables available
			$sql = "CREATE OR REPLACE FUNCTION create_" . $smw_tmp_unusedprops . "() RETURNS void AS "
				   . "$$ "
				   . "BEGIN "
				   . " IF EXISTS(SELECT NULL FROM pg_tables WHERE tablename='" . $smw_tmp_unusedprops . "' AND schemaname = ANY (current_schemas(true))) "
				   . " THEN DELETE FROM " . $smw_tmp_unusedprops . "; "
				   . " ELSE "
				   . "  CREATE TEMPORARY TABLE " . $smw_tmp_unusedprops . " ( title text ); "
				   . " END IF; "
				   . "END; "
				   . "$$ "
				   . "LANGUAGE 'plpgsql'; "
				   . "SELECT create_" . $smw_tmp_unusedprops . "(); ";
		} else { // MySQL: use temporary in-memory table
			$sql = "CREATE TEMPORARY TABLE " . $smw_tmp_unusedprops . "( title VARCHAR(255) ) ENGINE=MEMORY";
		}

		$db->query( $sql, __METHOD__ );

		$db->insertSelect( $smw_tmp_unusedprops, 'page', array( 'title' => 'page_title' ),
		                  array( "page_namespace" => SMW_NS_PROPERTY ),  __METHOD__ );

		$smw_ids = $db->tableName( 'smw_ids' );

		// all predefined properties are assumed to be used:
		$db->deleteJoin( $smw_tmp_unusedprops, $smw_ids, 'title', 'smw_title', array( 'smw_iw' => SMW_SQL2_SMWPREDEFIW ), __METHOD__ );

		// all tables occurring in some property table are used:
		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $proptable->fixedproperty == false ) { // MW does not seem to have a suitable wrapper for this
				$db->query( "DELETE FROM $smw_tmp_unusedprops USING $smw_tmp_unusedprops INNER JOIN " . $db->tableName( $proptable->name ) .
				" INNER JOIN $smw_ids ON p_id=smw_id AND title=smw_title AND smw_iw=" . $db->addQuotes( '' ), __METHOD__ );
			} // else: todo
		}

		// properties that have subproperties are considered to be used
		$propertyTables = self::getPropertyTables();
		$subPropertyTableId = self::$special_tables['_SUBP'];
		$subPropertyTable = $propertyTables[$subPropertyTableId];

		// (again we have no fitting MW wrapper here:)
		$db->query( "DELETE $smw_tmp_unusedprops.* FROM $smw_tmp_unusedprops," . $db->tableName( $subPropertyTable->name ) .
		           " INNER JOIN $smw_ids ON o_id=smw_id WHERE title=smw_title", __METHOD__ );
		// properties that are redirects are considered to be used:
		//   (a stricter and more costy approach would be to delete only redirects to used properties;
		//    this would need to be done with an addtional query in the above loop)
		// The redirect table is a fixed part of this store, no need to find its name.
		$db->deleteJoin( $smw_tmp_unusedprops, 'smw_redi2', 'title', 's_title', array( 's_namespace' => SMW_NS_PROPERTY ), __METHOD__ );

		$options = $this->getSQLOptions( $requestoptions, 'title' );
		$options['ORDER BY'] = 'title';
		$res = $db->select( $smw_tmp_unusedprops, 'title', '', __METHOD__, $options );

		$result = array();

		foreach ( $res as $row ) {
			$result[] = new SMWDIProperty( $row->title );
		}

		$db->freeResult( $res );

		$db->query( "DROP TEMPORARY table $smw_tmp_unusedprops", __METHOD__ );
		wfProfileOut( "SMWSQLStore2::getUnusedPropertiesSpecial (SMW)" );

		return $result;
	}

	/**
	 * Implementation of SMWStore::getWantedPropertiesSpecial(). Like all
	 * WantedFoo specials, this function is very resource intensive and needs
	 * to be cached on medium/large wikis.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of array( SMWDIProperty, int )
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		global $smwgPDefaultType;

		wfProfileIn( "SMWSQLStore2::getWantedPropertiesSpecial (SMW)" );

		// Note that Wanted Properties must have the default type.
		$proptables = self::getPropertyTables();
		$proptable = $proptables[self::findTypeTableId( $smwgPDefaultType )];

		$result = array();

		if ( $proptable->fixedproperty == false ) { // anything else would be crazy, but let's fail gracefully even if the whole world is crazy
			$db = wfGetDB( DB_SLAVE );
			$options = $this->getSQLOptions( $requestoptions, 'title' );
			$options['ORDER BY'] = 'count DESC';
			$res = $db->select( $db->tableName( $proptable->name ) . ' INNER JOIN ' .
				$db->tableName( 'smw_ids' ) . ' ON p_id=smw_id LEFT JOIN ' .
				$db->tableName( 'page' ) . ' ON (page_namespace=' .
				$db->addQuotes( SMW_NS_PROPERTY ) . ' AND page_title=smw_title)',
				'smw_title, COUNT(*) as count',
				'smw_id > 50 AND page_id IS NULL GROUP BY smw_title',
				'SMW::getWantedPropertiesSpecial', $options );

			foreach ( $res as $row ) {
				$result[] = array( new SMWDIProperty( $row->smw_title ), $row->count );
			}
		}

		wfProfileOut( "SMWSQLStore2::getWantedPropertiesSpecial (SMW)" );

		return $result;
	}

	public function getStatistics() {
		wfProfileIn( 'SMWSQLStore2::getStatistics (SMW)' );

		$db = wfGetDB( DB_SLAVE );
		$result = array();
		$proptables = self::getPropertyTables();

		// count number of declared properties by counting "has type" annotations
		$typeprop = new SMWDIProperty( '_TYPE' );
		$typetable = $proptables[self::findPropertyTableID( $typeprop )];
		$res = $db->select( $typetable->name, 'COUNT(s_id) AS count', array( 'p_id' => $this->getSMWPropertyID( $typeprop ) ), 'SMW::getStatistics' );
		$row = $db->fetchObject( $res );
		$result['DECLPROPS'] = $row->count;
		$db->freeResult( $res );

		// count property uses by counting rows in property tables,
		// count used properties by counting distinct properties in each table
		$result['PROPUSES'] = 0;
		$result['USEDPROPS'] = 0;

		foreach ( self::getPropertyTables() as $proptable ) {
			/// Note: subproperties that are part of container values are counted individually;
			/// It does not seem to be important to filter them by adding more conditions.
			$res = $db->select( $proptable->name, 'COUNT(*) AS count', '', 'SMW::getStatistics' );
			$row = $db->fetchObject( $res );
			$result['PROPUSES'] += $row->count;
			$db->freeResult( $res );

			if ( $proptable->fixedproperty == false ) {
				$res = $db->select( $proptable->name, 'COUNT(DISTINCT(p_id)) AS count', '', 'SMW::getStatistics' );
				$row = $db->fetchObject( $res );
				$result['USEDPROPS'] += $row->count;
			} else {
				$res = $db->select( $proptable->name, '*', '', 'SMW::getStatistics', array( 'LIMIT' => 1 ) );
				if ( $db->numRows( $res ) > 0 )  $result['USEDPROPS']++;
			}

			$db->freeResult( $res );
		}

		wfProfileOut( 'SMWSQLStore2::getStatistics (SMW)' );
		return $result;
	}

///// Setup store /////

	public function setup( $verbose = true ) {
		$this->reportProgress( "Setting up standard database configuration for SMW ...\n\n", $verbose );
		$this->reportProgress( "Selected storage engine is \"SMWSQLStore2\" (or an extension thereof)\n\n", $verbose );

		$db = wfGetDB( DB_MASTER );

		$this->setupTables( $verbose, $db );
		$this->setupPredefinedProperties( $verbose, $db );

		return true;
	}

	/**
	 * Create required SQL tables. This function also performs upgrades of
	 * table contents when required.
	 *
	 * Documentation for the table smw_ids: This table is normally used to
	 * store references to wiki pages (possibly with some external interwiki
	 * prefix). There are, however, some special objects that are also
	 * stored therein. These are marked by special interwiki prefixes (iw)
	 * that cannot occcur in real life:
	 *
	 * - Rows with iw SMW_SQL2_SMWREDIIW are similar to normal entries for
	 * (internal) wiki pages, but the iw indicates that the page is a
	 * redirect, the target of which should be sought using the smw_redi2
	 * table.
	 *
	 * - The (unique) row with iw SMW_SQL2_SMWBORDERIW just marks the
	 * border between predefined ids (rows that are reserved for hardcoded
	 * ids built into SMW) and normal entries. It is no object, but makes
	 * sure that SQL's auto increment counter is high enough to not add any
	 * objects before that marked "border".
	 */
	protected function setupTables( $verbose, $db ) {
		global $wgDBtype;

		$reportTo = $verbose ? $this : null; // Use $this to report back from static SMWSQLHelpers.

		// Repeatedly used DB field types defined here for convenience.
		$dbtypes = array(
			't' => SMWSQLHelpers::getStandardDBType( 'title' ),
			'l' => SMWSQLHelpers::getStandardDBType( 'blob' ),
			'f' => ( $wgDBtype == 'postgres' ? 'DOUBLE PRECISION' : 'DOUBLE' ),
			'i' => ( $wgDBtype == 'postgres' ? 'INTEGER' : 'INT(8)' ),
			'j' => ( $wgDBtype == 'postgres' ? 'INTEGER' : 'INT(8) UNSIGNED' ),
			'p' => SMWSQLHelpers::getStandardDBType( 'id' ),
			'n' => SMWSQLHelpers::getStandardDBType( 'namespace' ),
			'w' => SMWSQLHelpers::getStandardDBType( 'iw' )
		);

		$smw_spec2 = $db->tableName( 'smw_spec2' );

		// DB update: field renaming between SMW 1.3 and SMW 1.4.
		if ( ( $db->tableExists( $smw_spec2 ) ) && ( $db->fieldExists( $smw_spec2, 'sp_id', __METHOD__ ) ) ) {
			if ( $wgDBtype == 'postgres' ) {
				$db->query( "ALTER TABLE $smw_spec2 ALTER COLUMN sp_id RENAME TO p_id", __METHOD__ );
			} else {
				$db->query( "ALTER TABLE $smw_spec2 CHANGE `sp_id` `p_id` " . $dbtypes['p'] . " NOT NULL", __METHOD__ );
			}
		}

		// Set up table for internal IDs used in this store:
		SMWSQLHelpers::setupTable(
			'smw_ids',
			array(
				'smw_id' => $dbtypes['p'] . ' NOT NULL' . ( $wgDBtype == 'postgres' ? ' PRIMARY KEY' : ' KEY AUTO_INCREMENT' ),
				'smw_namespace' => $dbtypes['n'] . ' NOT NULL',
				'smw_title' => $dbtypes['t'] . ' NOT NULL',
				'smw_iw' => $dbtypes['w'] . ' NOT NULL',
				'smw_subobject' => $dbtypes['t'] . ' NOT NULL',
				'smw_sortkey' => $dbtypes['t']  . ' NOT NULL'
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex( 'smw_ids', array( 'smw_id', 'smw_title,smw_namespace,smw_iw', 'smw_title,smw_namespace,smw_iw,smw_subobject', 'smw_sortkey' ), $db );

		// Set up concept cache: member elements (s)->concepts (o)
		SMWSQLHelpers::setupTable(
			'smw_conccache',
			array(
				's_id' => $dbtypes['p'] . ' NOT NULL',
				'o_id' => $dbtypes['p'] . ' NOT NULL'
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex( 'smw_conccache', array( 'o_id' ), $db );

		// Set up all property tables as defined:
		$this->setupPropertyTables( $dbtypes, $db, $reportTo );

		$this->reportProgress( "Database initialised successfully.\n\n", $verbose );
	}

	/**
	 * Sets up the property tables.
	 *
	 * @param array $dbtypes
	 * @param $db
	 * @param $reportTo SMWSQLStore2 or null
	 */
	protected function setupPropertyTables( array $dbtypes, $db, $reportTo ) {
		$addedCustomTypeSignatures = false;

		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $proptable->idsubject ) {
				$fieldarray = array( 's_id' => $dbtypes['p'] . ' NOT NULL' );
				$indexes = array( 's_id' );
			} else {
				$fieldarray = array( 's_title' => $dbtypes['t'] . ' NOT NULL', 's_namespace' => $dbtypes['n'] . ' NOT NULL' );
				$indexes = array( 's_title,s_namespace' );
			}

			if ( !$proptable->fixedproperty ) {
				$fieldarray['p_id'] = $dbtypes['p'] . ' NOT NULL';
				$indexes[] = 'p_id';
			}

			foreach ( $proptable->objectfields as $fieldname => $typeid ) {
				// If the type signature is not recognized and the custom signatures have not been added, add them.
				if ( !$addedCustomTypeSignatures && !array_key_exists( $typeid, $dbtypes ) ) {
					wfRunHooks( 'SMWCustomSQLStoreFieldType', array( &$dbtypes ) );
					$addedCustomTypeSignatures = true;
				}

				// Only add the type when the signature was recognized, otherwise ignore it silently.
				if ( array_key_exists( $typeid, $dbtypes ) ) {
					$fieldarray[$fieldname] = $dbtypes[$typeid];
				}
			}

			$indexes = array_merge( $indexes, $proptable->indexes );

			SMWSQLHelpers::setupTable( $proptable->name, $fieldarray, $db, $reportTo );
			SMWSQLHelpers::setupIndex( $proptable->name, $indexes, $db );
		}
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having the DB contents predefined
	 * allows us to safe DB calls when certain data is needed. At the same time, the entries in the DB
	 * make sure that DB-based functions work as with all other properties.
	 */
	protected function setupPredefinedProperties( $verbose, $db ) {
		global $wgDBtype;

		$this->reportProgress( "Setting up internal property indices ...\n", $verbose );

		// Check if we already have this structure
		$borderiw = $db->selectField( 'smw_ids', 'smw_iw', 'smw_id=' . $db->addQuotes( 50 ) );

		if ( $borderiw != SMW_SQL2_SMWBORDERIW ) {
			$this->reportProgress( "   ... allocating space for internal properties ...\n", $verbose );
			$this->moveSMWPageID( 50 ); // make sure position 50 is empty

			$db->insert( 'smw_ids', array(
					'smw_id' => 50,
					'smw_title' => '',
					'smw_namespace' => 0,
					'smw_iw' => SMW_SQL2_SMWBORDERIW,
					'smw_subobject' => '',
					'smw_sortkey' => ''
				), 'SMW::setup'
			); // put dummy "border element" on index 50

			$this->reportProgress( '   ', $verbose );

			for ( $i = 0; $i < 50; $i++ ) { // make way for built-in ids
				$this->moveSMWPageID( $i );
				$this->reportProgress( '.', $verbose );
			}

			$this->reportProgress( "   done.\n", $verbose );
		} else {
			$this->reportProgress( "   ... space for internal properties already allocated.\n", $verbose );
		}

		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->reportProgress( "   ... writing entries for internal properties ...", $verbose );

		foreach ( self::$special_ids as $prop => $id ) {
			$p = new SMWDIProperty( $prop );
			$db->replace( 'smw_ids',	array( 'smw_id' ), array(
					'smw_id' => $id,
					'smw_title' => $p->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->getPropertyInterwiki( $p ),
					'smw_subobject' => '',
					'smw_sortkey' => $p->getKey()
				), 'SMW::setup'
			);
		}

		$this->reportProgress( " done.\n", $verbose );

		if ( $wgDBtype == 'postgres' ) {
			$this->reportProgress( " ... updating smw_ids_smw_id_seq sequence accordingly.\n", $verbose );

			$max = $db->selectField( 'smw_ids', 'max(smw_id)', array(), __METHOD__ );
			$max += 1;

			$db->query( "ALTER SEQUENCE smw_ids_smw_id_seq RESTART WITH {$max}", __METHOD__ );
		}

		$this->reportProgress( "Internal properties initialised successfully.\n", $verbose );
	}

	public function drop( $verbose = true ) {
		global $wgDBtype;

		$this->reportProgress( "Deleting all database content and tables generated by SMW ...\n\n", $verbose );
		$db = wfGetDB( DB_MASTER );
		$tables = array( 'smw_ids', 'smw_conccache' );

		foreach ( self::getPropertyTables() as $proptable ) {
			$tables[] = $proptable->name;
		}

		foreach ( $tables as $table ) {
			$name = $db->tableName( $table );
			$db->query( 'DROP TABLE' . ( $wgDBtype == 'postgres' ? '' : ' IF EXISTS' ) . $name, 'SMWSQLStore2::drop' );
			$this->reportProgress( " ... dropped table $name.\n", $verbose );
		}

		$this->reportProgress( "All data removed successfully.\n", $verbose );

		return true;
	}

	/**
	 * @see SMWStore::refreshData
	 * 
	 * @param integer $index
	 * @param integer $count
	 * @param mixed $namespaces Array or false
	 * @param boolean $usejobs
	 * 
	 * @return decimal between 0 and 1 to indicate the overall progress of the refreshing
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		$updatejobs = array();
		$emptyrange = true; // was nothing done in this run?

		// Update by MediaWiki page id --> make sure we get all pages.
		$tids = array();

		// Array of ids
		for ( $i = $index; $i < $index + $count; $i++ ) { 
			$tids[] = $i;
		}

		$titles = Title::newFromIDs( $tids );

		foreach ( $titles as $title ) {
			if ( ( $namespaces == false ) || ( in_array( $title->getNamespace(), $namespaces ) ) ) {
				$updatejobs[] = new SMWUpdateJob( $title );
				$emptyrange = false;
			}
		}

		// update by internal SMW id --> make sure we get all objects in SMW
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( 'smw_ids', array( 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw', 'smw_subobject' ),
				"smw_id >= $index AND smw_id < " . $db->addQuotes( $index + $count ), __METHOD__ );

		foreach ( $res as $row ) {
			$emptyrange = false; // note this even if no jobs were created

			if ( $namespaces && !in_array( $row->smw_namespace, $namespaces ) ) continue;

			if ( $row->smw_subobject !== '' ) {
				// leave subobjects alone; they ought to be changed with their pages
			} elseif ( $row->smw_iw === '' || $row->smw_iw == SMW_SQL2_SMWREDIIW ) { // objects representing pages
				// TODO: special treament of redirects needed, since the store will
				// not act on redirects that did not change according to its records
				$title = Title::makeTitleSafe( $row->smw_namespace, $row->smw_title );

				if ( $title !== null && !$title->exists() ) {
					$updatejobs[] = new SMWUpdateJob( $title );
				}
			} elseif ( $row->smw_iw == SMW_SQL2_SMWIW_OUTDATED ) { // remove outdated internal object references
				foreach ( self::getPropertyTables() as $proptable ) {
					if ( $proptable->idsubject ) {
						$db->delete( $proptable->name, array( 's_id' => $row->smw_id ), __METHOD__ );
					}
				}
				$db->delete( 'smw_ids',	array( 'smw_id' => $row->smw_id ), __METHOD__ );
			} else { // "normal" interwiki pages or outdated internal objects
				$diWikiPage = new SMWDIWikiPage( $row->smw_title, $row->smw_namespace, $row->smw_iw );
				$this->deleteSemanticData( $diWikiPage );
			} 
		}
		$db->freeResult( $res );

		wfRunHooks('smwRefreshDataJobs', array(&$updatejobs));

		if ( $usejobs ) {
			Job::batchInsert( $updatejobs );
		} else {
			foreach ( $updatejobs as $job ) {
				$job->run();
			}
		}

		$nextpos = $index + $count;

		if ( $emptyrange ) { // nothing found, check if there will be more pages later on
			$next1 = $db->selectField( 'page', 'page_id', "page_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "page_id ASC" ) );
			$next2 = $db->selectField( 'smw_ids', 'smw_id', "smw_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "smw_id ASC" ) );
			$nextpos = ( ( $next2 != 0 ) && ( $next2 < $next1 ) ) ? $next2 : $next1;
		}

		$max1 = $db->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$max2 = $db->selectField( 'smw_ids', 'MAX(smw_id)', '', __METHOD__ );
		$index = $nextpos ? $nextpos : -1;

		return ( $index > 0 ) ? $index / max( $max1, $max2 ) : 1;
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
		wfProfileIn( 'SMWSQLStore2::refreshConceptCache (SMW)' );
		global $smwgIP;

		include_once( "$smwgIP/includes/storage/SMW_SQLStore2_Queries.php" );

		$qe = new SMWSQLStore2QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->refreshConceptCache( $concept );

		wfProfileOut( 'SMWSQLStore2::refreshConceptCache (SMW)' );

		return $result;
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache( $concept ) {
		wfProfileIn( 'SMWSQLStore2::deleteConceptCache (SMW)' );
		global $smwgIP;

		include_once( "$smwgIP/includes/storage/SMW_SQLStore2_Queries.php" );

		$qe = new SMWSQLStore2QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->deleteConceptCache( $concept );

		wfProfileOut( 'SMWSQLStore2::deleteConceptCache (SMW)' );

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
		wfProfileIn( 'SMWSQLStore2::getConceptCacheStatus (SMW)' );

		$db = wfGetDB( DB_SLAVE );
		$cid = $this->getSMWPageID( $concept->getDBkey(), $concept->getNamespace(), '', '', false );

		$row = $db->selectRow( 'smw_conc2',
		         array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date', 'cache_count' ),
		         array( 's_id' => $cid ), 'SMWSQLStore2::getConceptCacheStatus (SMW)' );

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

		wfProfileOut( 'SMWSQLStore2::getConceptCacheStatus (SMW)' );

		return $result;
	}


///// Helper methods, mostly protected /////

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 * The parameter $valuecol defines the string name of the column to which
	 * sorting requests etc. are to be applied.
	 */
	protected function getSQLOptions( $requestoptions, $valuecol = '' ) {
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
	protected function getSQLConditions( $requestoptions, $valuecol = '', $labelcol = '', $addand = true ) {
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
	protected function applyRequestOptions( $data, $requestoptions ) {
		wfProfileIn( "SMWSQLStore2::applyRequestOptions (SMW)" );

		if ( ( count( $data ) == 0 ) || is_null( $requestoptions ) ) {
			wfProfileOut( "SMWSQLStore2::applyRequestOptions (SMW)" );
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

		wfProfileOut( "SMWSQLStore2::applyRequestOptions (SMW)" );

		return $result;
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 */
	public function reportProgress( $msg, $verbose = true ) {
		if ( $verbose ) {
			if ( ob_get_level() == 0 ) { // be sure to have some buffer, otherwise some PHPs complain
				ob_start();
			}

			print $msg;
			ob_flush();
			flush();
		}
	}

	/**
	 * For a given SMW type id, obtain the "signature" from which the
	 * appropriate property table and information about sorting/filtering
	 * data of this type can be obtained. The result is an array of three
	 * entries: a signature string, the index of the value field, and
	 * the index of the label label field.
	 */
	public static function getTypeSignature( $typeid ) {
		$dataItemId = SMWDataValueFactory::getDataItemId( $typeid );
		return array( SMWCompatibilityHelpers::getSignatureFromDataItemId( $dataItemId, $typeid ),
		              SMWCompatibilityHelpers::getIndexFromDataItemId( $dataItemId, $typeid, false ),
		              SMWCompatibilityHelpers::getIndexFromDataItemId( $dataItemId, $typeid, true ) );
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
		if ( array_key_exists( $propertyKey, self::$special_tables ) ) {
			return self::$special_tables[$propertyKey];
		} else {
			return self::findTypeTableId( $diProperty->findPropertyTypeID() );
		}
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
			$iw = SMW_SQL2_SMWREDIIW;
			$id = $this->m_idCache->getId( $title, $namespace, SMW_SQL2_SMWREDIIW, $subobjectName );
		}

		if ( $id == 0 || !$canonical || $iw != SMW_SQL2_SMWREDIIW ) {
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
		wfProfileIn( 'SMWSQLStore2::getSMWPageID (SMW)' );

		global $smwgQEqualitySupport;

		$db = wfGetDB( DB_SLAVE );

		if ( $iw !== '' && !is_null( $iw ) ) { // external page; no need to think about redirects
			$iwCond = 'smw_iw=' . $db->addQuotes( $iw );
		} else {
			$iwCond = '(smw_iw=' . $db->addQuotes( '' ) .
				' OR smw_iw=' . $db->addQuotes( SMW_SQL2_SMWREDIIW ) . ')';
		}

		$row = $db->selectRow( 'smw_ids', array( 'smw_id', 'smw_iw', 'smw_sortkey' ),
			'smw_title=' . $db->addQuotes( $title ) .
			' AND smw_namespace=' . $db->addQuotes( $namespace ) .
			" AND $iwCond AND smw_subobject=" . $db->addQuotes( $subobjectName ),
			__METHOD__ );

		if ( $row !== false ) {
			$sort = $row->smw_sortkey;
			$this->m_idCache->setId( $title, $namespace, $row->smw_iw, $subobjectName, $row->smw_id );

			if ( $row->smw_iw == SMW_SQL2_SMWREDIIW && $canonical &&
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

		wfProfileOut( 'SMWSQLStore2::getSMWPageID (SMW)' );
		return $id;
	}

	public function getRedirectId( $title, $namespace ) {
		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( 'smw_redi2', 'o_id',
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
	protected function makeSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $sortkey = '' ) {
		wfProfileIn( 'SMWSQLStore2::makeSMWPageID (SMW)' );

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

		wfProfileOut( 'SMWSQLStore2::makeSMWPageID (SMW)' );
		return $id;
	}

	/**
	 * Properties have a mechanisms for being predefined (i.e. in PHP instead
	 * of in wiki). Special "interwiki" prefixes separate the ids of such
	 * predefined properties from the ids for the current pages (which may,
	 * e.g., be moved, while the predefined object is not movable).
	 */
	protected function getPropertyInterwiki( SMWDIProperty $property ) {
		if ( $property->isUserDefined() ) {
			return '';
		} else {
			return ( $property->getLabel() !== '' ) ? SMW_SQL2_SMWPREDEFIW : SMW_SQL2_SMWINTDEFIW;
		}
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
	protected function makeSMWPropertyID( SMWDIProperty $property ) {
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
	 * with iw being SMW_SQL2_SMWREDIIW. This information is used to determine
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
	protected function moveSMWPageID( $curid, $targetid = 0 ) {
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

		$db->delete( 'smw_ids', array( 'smw_id' => $curid ), 'SMWSQLStore2::moveSMWPageID' );

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
	protected function changeSMWPageID( $oldid, $newid, $oldnamespace = -1,
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

				foreach ( $proptable->objectfields as $fieldname => $type ) {
					if ( $type == 'p' ) {
						$db->update( $proptable->name, array( $fieldname => $newid ), array( $fieldname => $oldid ), __METHOD__ );
					}
				}
			}
		}
		
		// Change id entries in concept-related tables:
		if ( $sdata && ( ( $oldnamespace == -1 ) || ( $oldnamespace == SMW_NS_CONCEPT ) ) ) {
			if ( ( $newnamespace == -1 ) || ( $newnamespace == SMW_NS_CONCEPT ) ) {
				$db->update( 'smw_conc2', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
				$db->update( 'smw_conccache', array( 's_id' => $newid ), array( 's_id' => $oldid ), __METHOD__ );
			} else {
				$db->delete( 'smw_conc2', array( 's_id' => $oldid ), __METHOD__ );
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
	protected function deleteSemanticData( SMWDIWikiPage $subject ) {
		if ( $subject->getSubobjectName() !== '' ) return; // not needed, and would mess up data

		$db = wfGetDB( DB_MASTER );

		$id = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );

		if ( $id == 0 ) {
			// not (directly) used anywhere yet, may be a redirect but we do not care here
			wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
			return;
		}

		foreach ( self::getPropertyTables() as $proptable ) {
			if ( $proptable->name == 'smw_conc2' ) continue; // skip concepts, since they have chache data in their table which should be kept while the cache is intact
			if ( $proptable->idsubject ) {
				$db->delete( $proptable->name, array( 's_id' => $id ), __METHOD__ );
			} elseif ( $proptable->name != 'smw_redi2' ) { /// NOTE: redirects are handled by updateRedirects(), not here!
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
	protected function updateRedirects( $subject_t, $subject_ns, $curtarget_t = '', $curtarget_ns = -1 ) {
		global $smwgQEqualitySupport, $smwgEnableUpdateJobs;

		// *** First get id of subject, old redirect target, and current (new) redirect target ***//

		$sid = $this->getSMWPageID( $subject_t, $subject_ns, '', '', false ); // find real id of subject, if any
		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$new_tid = $curtarget_t ? ( $this->makeSMWPageID( $curtarget_t, $curtarget_ns, '', '', false ) ) : 0; // real id of new target, if given

		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( array( 'smw_redi2' ), 'o_id',
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
			$db->delete( 'smw_redi2',
				array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), __METHOD__ );

			if ( $smwgEnableUpdateJobs && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// entries that refer to old target may in fact refer to subject,
				// but we don't know which: schedule affected pages for update
				$jobs = array();

				foreach ( self::getPropertyTables() as $proptable ) {
					if ( $proptable->name == 'smw_redi2' ) continue; // can safely be skipped

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

					foreach ( $proptable->objectfields as $fieldname => $type ) {
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
			// (1) make a new ID with iw SMW_SQL2_SMWREDIIW or
			//     change iw field of current ID in this way,
			// (2) write smw_redi2 table,
			// (3) update canonical cache.
			// This order must be obeyed unless you really understand what you are doing!

			if ( ( $old_tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// mark subject as redirect (if it was no redirect before)
				if ( $sid == 0 ) { // every redirect page must have an ID
					$sid = $this->makeSMWPageID( $subject_t, $subject_ns,
						SMW_SQL2_SMWREDIIW, '', false );
				} else {
					$db->update( 'smw_ids', array( 'smw_iw' => SMW_SQL2_SMWREDIIW ),
						array( 'smw_id' => $sid ), __METHOD__ );
					$this->m_idCache->setId( $subject_t, $subject_ns, '', '', 0 );
					$this->m_idCache->setId( $subject_t, $subject_ns, SMW_SQL2_SMWREDIIW, '', $sid );
				}
			}

			$db->insert( 'smw_redi2', array( 's_title' => $subject_t,
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
	 * it if necessary. The result is an array of SMWSQLStore2Table objects
	 * indexed by table ids. Note that the ids are only for accessing the data
	 * and should not be assumed to agree with the table name.
	 *
	 * Tables declare value columns ("object fields") by specifying their name
	 * and type. Types are given using letters:
	 * - t for strings of the same maximal length as MediaWiki title names,
	 * - l for arbitrarily long strings; searching/sorting with such data may
	 * be limited for performance reasons,
	 * - w for strings as used in MediaWiki for encoding interwiki prefixes
	 * - n for namespace numbers (or other similar integers)
	 * - f for floating point numbers of double precision 
	 * - p for a reference to an SMW ID as stored in the smw_ids table; this
	 *   corresponds to a data entry of ID "tnwt".
	 *
	 * @return array of SMWSQLStore2Table
	 * @todo The concept table should make s_id a primary key; make this possible.
	 */
	public static function getPropertyTables() {
		if ( count( self::$prop_tables ) > 0 ) return self::$prop_tables; // Don't initialise twice.

		self::$prop_tables['smw_rels2'] = new SMWSQLStore2Table(
			'smw_rels2',
			array( 'o_id' => 'p' ),
			array( 'o_id' )
		);
		
		self::$prop_tables['smw_atts2'] = new SMWSQLStore2Table(
			'smw_atts2',
			array( 'value_xsd' => 't', 'value_num' => 'f' ),
			array( 'value_num', 'value_xsd' )
		);
		
		self::$prop_tables['smw_text2'] = new SMWSQLStore2Table(
			'smw_text2',
			array( 'value_blob' => 'l' )
		);
		
		self::$prop_tables['smw_spec2'] = new SMWSQLStore2Table(
			'smw_spec2',
			array( 'value_string' => 't' ),
			array( 's_id,p_id' )
		);
		self::$prop_tables['smw_spec2']->specpropsonly = true;
		
		self::$prop_tables['smw_subs2'] = new SMWSQLStore2Table(
			'smw_subs2',
			array( 'o_id' => 'p' ),
			array( 'o_id' ),
			'_SUBC'
		);
		
		self::$prop_tables['smw_subp2'] = new SMWSQLStore2Table(
			'smw_subp2',
		    array( 'o_id' => 'p' ),
			array( 'o_id' ),
			'_SUBP'
		);
		
		self::$prop_tables['smw_inst2'] = new SMWSQLStore2Table(
			'smw_inst2',
		    array( 'o_id' => 'p' ),
			array( 'o_id' ),
			'_INST'
		);
		
		self::$prop_tables['smw_redi2'] = new SMWSQLStore2Table(
			'smw_redi2',
			array( 'o_id' => 'p' ),
			array( 'o_id' ),
			'_REDI'
		);
		self::$prop_tables['smw_redi2']->idsubject = false;

		self::$prop_tables['smw_conc2'] = new SMWSQLStore2Table(
			'smw_conc2',
			array( 'concept_txt' => 'l', 'concept_docu' => 'l', 'concept_features' => 'n', 'concept_size' => 'n', 'concept_depth' => 'n','cache_date' => 'j', 'cache_count' => 'j' ),
			array( ),
			'_CONC'
		);
		
		self::$prop_tables['smw_coords'] = new SMWSQLStore2Table(
			'sm_coords',
			array( 'lat' => 'f', 'lon' => 'f', 'alt' => 'f' ),
			array( 'lat', 'lon', 'alt' )
		);		

		wfRunHooks( 'SMWPropertyTables', array( &self::$prop_tables ) );

		foreach ( self::$prop_tables as $tid => $proptable ) { // fixed property tables are added to known "special" tables
			if ( $proptable->fixedproperty != false ) {
				self::$special_tables[$proptable->fixedproperty] = $tid;
			}
		}

		return self::$prop_tables;
	}

}
