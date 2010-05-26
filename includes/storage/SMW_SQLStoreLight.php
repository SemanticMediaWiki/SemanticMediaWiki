<?php
/**
 * New SQL implementation of SMW's storage abstraction layer with
 * a reduced feature set for the SMWLight version. Statistic features
 * and semantic queries are completely disabled for now.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWStore
 */

// The use of the following constants is explained in SMWSQLStoreLight::setup():
define( 'SMW_SQL2_SMWIW', ':smw' ); // virtual "interwiki prefix" for special SMW objects
define( 'SMW_SQL2_SMWREDIIW', ':smw-redi' ); // virtual "interwiki prefix" for SMW objects that are redirected
define( 'SMW_SQL2_SMWBORDERIW', ':smw-border' ); // virtual "interwiki prefix" separating very important pre-defined properties from the rest
define( 'SMW_SQL2_SMWPREDEFIW', ':smw-preprop' ); // virtual "interwiki prefix" marking predefined objects (non-movable)
define( 'SMW_SQL2_SMWINTDEFIW', ':smw-intprop' ); // virtual "interwiki prefix" marking internal (invisible) predefined properties

/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data. This is a lightweight version of SMW's standard
 * storage implementation, providing only basic data storage and retrieval but
 * no querying or statistics.
 *
 * The store could be made lighter in various ways:
 * (1) Store values for all datatypes in a collapsed string format instead of
 * having tables for various datatypes. Strings are sufficient for simple
 * matching and fast retrieval, and even for some forms of queries.
 * (2) Remove ID management and use strings instead. ID management especially
 * causes a lot of (very simple) DB read requests in all operations that could
 * be a problem in certain server setups.
 * (3) Remove redirect handling. Currently redirects are used for identifying
 * pages, so that their different names can all be used when linking to them
 * without any difference to the semantics. This is convenient but not so
 * important when no queries are used anyway. Redirect handling creates some
 * complications in moving pages and is probably the most difficult code in
 * here.
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
class SMWSQLStoreLight extends SMWStore {

	/// Cache for SMW IDs, indexed by string keys
	protected $m_ids = array();

	/// Cache for SMWSemanticData objects, indexed by SMW ID
	protected $m_semdata = array();
	/// Like SMWSQLStoreLight::m_semdata, but containing flags indicating completeness of the SMWSemanticData objs
	protected $m_sdstate = array();
	/// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data
	protected static $in_getSemanticData = 0;

	/// Array for keeping property table table data, indexed by table id.
	/// Access this only by calling getPropertyTables().
	private static $prop_tables = array();
	/// Array to cache "propkey => propid" associations. Built only when needed.
	private static $fixed_prop_tables = null;

	/// Use pre-defined ids for Very Important Properties, avoiding frequent ID lookups for those
	private static $special_ids = array(
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
		'_1' => 23, // properties for encoding (short) lists
		'_2' => 24,
		'_3' => 25,
		'_4' => 26,
		'_5' => 27,
		'_LIST' => 28,
	);

	/// Array to cache ids of tables for storing known built-in types. Having
	/// this data here shortcuts the search in findTypeTableID() below.
	private static $property_table_ids = array(
		'_txt'  => 'smw_text2', // Text type
		'_cod'  => 'smw_text2', // Code type
		'_str'  => 'smw_atts2', // String type
		'_ema'  => 'smw_atts2', // Email type
		'_uri'  => 'smw_atts2', // URL/URI type
		'_anu'  => 'smw_atts2', // Annotation URI type
		'_tel'  => 'smw_atts2', // Telephone number
		'_wpg'  => 'smw_rels2', // Page type
		'_wpp'  => 'smw_rels2', // Property page type
		'_wpc'  => 'smw_rels2', // Category page type
		'_wpf'  => 'smw_rels2', // Form page type (for Semantic Forms)
		'_num'  => 'smw_atts2', // Number type
		'_tem'  => 'smw_atts2', // Temperature type
		'_dat'  => 'smw_atts2', // Time type
		'_geo'  => 'smw_atts2', // Geographic coordinates type
		'_boo'  => 'smw_atts2', // Boolean type
		'_rec'  => 'smw_rels2', // Value list type (internal object)
		// Special types are not avaialble directly for users (and have no local language name):
		'__typ' => 'smw_spec2', // Special type page type
		'__tls' => 'smw_spec2', // Special type list for _rec properties
		'__sps' => 'smw_spec2', // Special string type
		'__spu' => 'smw_spec2', // Special uri type
		'__sup' => 'smw_subp2', // Special subproperty type
		'__suc' => 'smw_subs2', // Special subcategory type
		'__spf' => 'smw_spec2', // Special form type (for Semantic Forms)
		'__sin' => 'smw_inst2', // Special instance of type
		'__red' => 'smw_redi2', // Special redirect type
		'__lin' => 'smw_spec2', // Special linear unit conversion type
		'__imp' => 'smw_spec2', // Special import vocabulary type
		'__err' => '',  // Special error type, used to indicate that the table could not be determined (happens for type-polymorphic _1, _2, ...)
		// '__pro' => SMW_SQL2_NONE,  // Property page type; actually this should never be stored as a value (_wpp is used there)
	);

	/// Array to cache signatures of known built-in types. Having this data
	/// here safes us from creating datavalue instances in getTypeSignature().
	private static $type_signatures = array(
		'_txt'  => array( 'l', -1, -1 ),  // Text type
		'_cod'  => array( 'l', -1, -1 ),  // Code type
		'_str'  => array( 't', 0, 0 ),    // String type
		'_ema'  => array( 't', 0, 0 ),    // Email type
		'_uri'  => array( 't', 0, 0 ),    // URL/URI type
		'_anu'  => array( 't', 0, 0 ),    // Annotation URI type
		'_tel'  => array( 't', 0, 0 ),    // Telephone number
		'_wpg'  => array( 'tnwt', 3, 3 ), // Page type
		'_wpp'  => array( 'tnwt', 3, 3 ), // Property page type
		'_wpc'  => array( 'tnwt', 3, 3 ), // Category page type
		'_wpf'  => array( 'tnwt', 3, 3 ), // Form page type (for Semantic Forms)
		'_num'  => array( 'tfu', 1, 0 ),  // Number type
		'_tem'  => array( 'tfu', 1, 0 ),  // Temperature type
		'_dat'  => array( 'tf', 1, 0 ),   // Time type
		'_boo'  => array( 't', 0, 0 ),    // Boolean type
		'_rec'  => array( 'tnwt', 0, -1 ),// Value list type (internal object)
		// Special types are not avaialble directly for users (and have no local language name):
		'__typ' => array( 't', 0, 0 ),    // Special type page type
		'__tls' => array( 't', 0, 0 ),    // Special type page type
		'__sps' => array( 't', 0, 0 ),    // Special string type
		'__spu' => array( 't', 0, 0 ),    // Special uri type
		'__sup' => array( 'tnwt', 3, 3 ), // Special subproperty type
		'__suc' => array( 'tnwt', 3, 3 ), // Special subcategory type
		'__spf' => array( 't', 0, 0 ),    // Special form type (for Semantic Forms)
		'__sin' => array( 'tnwt', 3, 3 ), // Special instance of type
		'__red' => array( 'tnwt', 3, 3 ), // Special redirect type
		'__lin' => array( 'tfu', 1, 0 ),  // Special linear unit conversion type
		'__imp' => array( 't', 0, 0 ), // Special import vocabulary type
		'__pro' => array( 't', 0, 0 ),  // Property page type; never be stored as a value (_wpp is used there) but needed for sorting
	);

///// Reading methods /////

	public function getSemanticData( $subject, $filter = false ) {
		wfProfileIn( "SMWSQLStoreLight::getSemanticData (SMW)" );
		SMWSQLStoreLight::$in_getSemanticData++; // do not clear the cache when called recursively
		// *** Find out if this subject exists ***//
		if ( $subject instanceof Title ) { ///TODO: can this still occur?
			$sid = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki() );
			$svalue = SMWWikiPageValue::makePageFromTitle( $subject );
		} elseif ( $subject instanceof SMWWikiPageValue ) {
			$sid =  $subject->isValid() ?
			        $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki() ):
					0;
			$svalue = $subject;
		} else {
			$sid = 0;
		}
		if ( $sid == 0 ) { // no data, safe our time
			/// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
			SMWSQLStoreLight::$in_getSemanticData--;
			wfProfileOut( "SMWSQLStoreLight::getSemanticData (SMW)" );
			return isset( $svalue ) ? ( new SMWSemanticData( $svalue ) ):null;
		}
		// *** Prepare the cache ***//
		if ( !array_key_exists( $sid, $this->m_semdata ) ) { // new cache entry
			$this->m_semdata[$sid] = new SMWSemanticData( $svalue, false );
			$this->m_sdstate[$sid] = array();
		}
		if ( ( count( $this->m_semdata ) > 20 ) && ( SMWSQLStoreLight::$in_getSemanticData == 1 ) ) {
			// prevent memory leak;
			// It is not so easy to find the sweet spot between cache size and performance gains (both memory and time),
			// The value of 20 was chosen by profiling runtimes for large inline queries and heavily annotated pages.
			$this->m_semdata = array( $sid => $this->m_semdata[$sid] );
			$this->m_sdstate = array( $sid => $this->m_sdstate[$sid] );
		}
		// *** Read the data ***//
		foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $proptable ) {
			if ( array_key_exists( $tid, $this->m_sdstate[$sid] ) ) continue;
			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeid ) {
					$relevant = $relevant || SMWSQLStoreLight::tableFitsType( $tid, $typeid );
				}
				if ( !$relevant ) continue;
			}
			$data = $this->fetchSemanticData( $sid, $svalue, $proptable );
			foreach ( $data as $d ) {
				$this->m_semdata[$sid]->addPropertyStubValue( reset( $d ), end( $d ) );
			}
			$this->m_sdstate[$sid][$tid] = true;
		}

		SMWSQLStoreLight::$in_getSemanticData--;
		wfProfileOut( "SMWSQLStoreLight::getSemanticData (SMW)" );
		return $this->m_semdata[$sid];
	}

	public function getPropertyValues( $subject, SMWPropertyValue $property, $requestoptions = null, $outputformat = '' ) {
		wfProfileIn( "SMWSQLStoreLight::getPropertyValues (SMW)" );
		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = clone $property;
			$noninverse->setInverse( false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestoptions );
		} elseif ( $subject !== null ) { // subject given, use semantic data cache:
			$sd = $this->getSemanticData( $subject, array( $property->getPropertyTypeID() ) );
			$result = $this->applyRequestOptions( $sd->getPropertyValues( $property ), $requestoptions );
			if ( $outputformat != '' ) { // reformat cached values
				$newres = array();
				foreach ( $result as $dv ) {
					$ndv = clone $dv;
					$ndv->setOutputFormat( $outputformat );
					$newres[] = $ndv;
				}
				$result = $newres;
			}
		} else { // no subject given, get all values for the given property
			$pid = $this->getSMWPropertyID( $property );
			$tableid = SMWSQLStoreLight::findPropertyTableID( $property );
			if ( ( $pid == 0 ) || ( $tableid == '' ) ) {
				wfProfileOut( "SMWSQLStoreLight::getPropertyValues (SMW)" );
				return array();
			}
			$proptables = SMWSQLStoreLight::getPropertyTables();
			$data = $this->fetchSemanticData( $pid, $property, $proptables[$tableid], false, $requestoptions );
			$result = array();
			foreach ( $data as $dbkeys ) {
				$dv = SMWDataValueFactory::newPropertyObjectValue( $property );
				if ( $outputformat != '' ) $dv->setOutputFormat( $outputformat );
				$dv->setDBkeys( $dbkeys );
				$result[] = $dv;
			}
		}
		wfProfileOut( "SMWSQLStoreLight::getPropertyValues (SMW)" );
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
	 * (being an SMWPageValue or SMWPropertyValue). Moreover, when filtering by property, it is
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
	 */
	protected function fetchSemanticData( $id, $object, $proptable, $issubject = true, $requestoptions = null ) {
		// stop if there is not enough data:
		// properties always need to be given as object, subjects at least if !$proptable->idsubject
		if ( ( $id == 0 ) || ( ( $object === null ) && ( !$issubject || !$proptable->idsubject ) ) ) return array();
		wfProfileIn( "SMWSQLStoreLight::fetchSemanticData-" . $proptable->name .  " (SMW)" );
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
		$pagevalues = array(); // collect indices of page-type components of this table (typically at most 1)
		$usedistinct = true; // use DISTINCT option only if no text blobs are among values
		$selectvalues = array(); // array for all values to be selected, kept to help finding value and label fields below
		foreach ( $proptable->objectfields as $fieldname => $typeid ) { // now add select entries for object column(s)
			if ( $typeid == 'p' ) { // Special case: page id, use smw_id table to insert 4 page-specific values instead of internal id
				$from .= ' INNER JOIN ' . $db->tableName( 'smw_ids' ) . " AS o$valuecount ON $fieldname=o$valuecount.smw_id";
				$select .= ( ( $select != '' ) ? ',':'' ) . "$fieldname AS id$valuecount";
				$selectvalues[$valuecount] = "o$valuecount.smw_title";
				$selectvalues[$valuecount + 1] = "o$valuecount.smw_namespace";
				$selectvalues[$valuecount + 2] = "o$valuecount.smw_iw";
				$selectvalues[$valuecount + 3] = "o$valuecount.smw_sortkey";
				$pagevalues[] = $valuecount;
				$valuecount += 3;
			} else { // just use value as given
				$selectvalues[$valuecount] = $fieldname;
			}
			if ( $typeid == 'l' ) $usedistinct = false;
			$valuecount++;
		}
		foreach ( $selectvalues as $index => $field ) {
			$select .= ( ( $select != '' ) ? ',':'' ) . "$field AS v$index";
		}
		if ( !$issubject ) { // needed to apply sorting/string matching in query; only with fixed property
			list( $sig, $valueindex, $labelindex ) = SMWSQLStoreLight::getTypeSignature( $object->getPropertyTypeID() );
			$valuecolumn = ( array_key_exists( $valueindex, $selectvalues ) ) ? $selectvalues[$valueindex]:'';
			$labelcolumn = ( array_key_exists( $labelindex, $selectvalues ) ) ? $selectvalues[$labelindex]:'';
			$where .= $this->getSQLConditions( $requestoptions, $valuecolumn, $labelcolumn, $where != '' );
		} else {
			$valuecolumn = $labelcolumn = '';
		}

		// ***  Now execute the query and read the results  ***//
		$res = $db->select( $from, $select, $where, 'SMW::getSemanticData',
		       ( $usedistinct ? $this->getSQLOptions( $requestoptions, $valuecolumn ) + array( 'DISTINCT' ):
			                 $this->getSQLOptions( $requestoptions, $valuecolumn ) ) );
		while ( $row = $db->fetchObject( $res ) ) {
			if ( !$issubject ) {
				$propertyname = 'fixed'; // irrelevant, but use this to check if the data is good
			} elseif ( !$proptable->fixedproperty ) { // use joined or predefined property name
				if ( $proptable->specpropsonly ) {
					$propertyname = array_search( $row->p_id, SMWSQLStoreLight::$special_ids );
					if ( $propertyname === false ) { // unknown property that uses a special type, maybe by some extension; look it up in the DB
						// NOTE: this is just an emergency fallback but not a fast solution; extensions may prefer to use non-special datatypes for new properties!
						$propertyname = $db->selectField( 'smw_ids', 'smw_title', array( 'smw_id' => $row->p_id ), 'SMW::getSemanticData-LatePropertyFetch' );
					}
				} else {
					$propertyname = $row->prop;
				}
			} else { // use fixed property name
				$propertyname = $proptable->fixedproperty;
			}
			$valuekeys = array();
			reset( $pagevalues );
			for ( $i = 0; $i < $valuecount; $i++ ) { // read the value fields from the current row
				$fieldname = "v$i";
				$newvalue = $row->$fieldname;
				if ( $i === current( $pagevalues ) ) { // special check for pages to filter out internal objects
					$iwfield = 'v' . ( $i + 2 );
					$iw = $row->$iwfield;
					if ( ( $iw == SMW_SQL2_SMWIW ) && ( $valuecount == 4 ) && ( $object !== null ) ) {
						// read container objects recursively; but only if proptable is of form "p"
						// also avoid (hypothetical) double recursion by requiring $object!==null
						$i += 3; // skip other page fields of this bnode
						$oidfield = 'id' . current( $pagevalues );
						$newvalue = array();
						foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $pt ) { // just read all
							$newvalue = array_merge( $newvalue, $this->fetchSemanticData( $row->$oidfield, null, $pt ) );
						}
					} elseif ( ( $iw != '' ) && ( $iw { 0 } == ':' ) ) { // other internal object, maybe a DB inconsistency; ignore row
						$propertyname = '';
					}
					next( $pagevalues );
				}
				$valuekeys[] = $newvalue;
			}
			if ( $propertyname != '' ) $result[] = $issubject ? array( $propertyname, $valuekeys ):$valuekeys;
		}
		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStoreLight::fetchSemanticData-" . $proptable->name .  " (SMW)" );
		return $result;
	}

	public function getPropertySubjects( SMWPropertyValue $property, $value, $requestoptions = null ) {
		/// TODO: should we share code with #ask query computation here? Just use queries?
		wfProfileIn( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = clone $property;
			$noninverse->setInverse( false );
			$result = $this->getPropertyValues( $value, $noninverse, $requestoptions );
			wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
			return $result;
		}

		// ***  First build $select, $from, and $where for the DB query  ***//
		$select = $where = $from = '';
		$pid = $this->getSMWPropertyID( $property );
		$tableid = SMWSQLStoreLight::findPropertyTableID( $property );
		if ( ( $tableid == '' ) && ( $value !== null ) ) { // maybe a type-polymorphic property like _1; use value to find type
			$tableid = SMWSQLStoreLight::findTypeTableID( $value->getTypeID() );
		}
		if ( ( $pid == 0 ) || ( $tableid == '' ) || ( ( $value !== null ) && ( !$value->isValid() ) ) ) {
			return array();
		}
		$proptables = SMWSQLStoreLight::getPropertyTables();
		$proptable = $proptables[$tableid];
		$db = wfGetDB( DB_SLAVE );
		if ( $proptable->idsubject ) { // join in smw_ids to get title data
			$from = $db->tableName( 'smw_ids' ) . " INNER JOIN " . $db->tableName( $proptable->name ) . " AS t1 ON t1.s_id=smw_id";
			$select = 'smw_title AS title, smw_namespace AS namespace, smw_sortkey';
		} else { // no join needed, title+namespace as given in proptable
			$from = $db->tableName( $proptable->name ) . " AS t1";
			$select = 's_title AS title, s_namespace AS namespace, s_title AS smw_sortkey';
		}
		if ( $proptable->fixedproperty == false ) {
			$where .= ( $where ? ' AND ':'' ) . "t1.p_id=" . $db->addQuotes( $pid );
		}
		$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

		// ***  Now execute the query and read the results  ***//
		$result = array();
		$res = $db->select( $from, 'DISTINCT ' . $select,
		                    $where . $this->getSQLConditions( $requestoptions, 'smw_sortkey', 'smw_sortkey', $where != '' ),
							'SMW::getPropertySubjects',
		                    $this->getSQLOptions( $requestoptions, 'smw_sortkey' ) );
		while ( $row = $db->fetchObject( $res ) ) {
			$result[] = SMWWikiPageValue::makePage( $row->title, $row->namespace, $row->smw_sortkey );
		}
		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
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
			// $oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki(),false);
			///NOTE: we do not use the canonical (redirect-aware) id here!
	 */
	protected function prepareValueQuery( &$from, &$where, $proptable, $value, $tableindex = 1 ) {
		$db = wfGetDB( DB_SLAVE );
		if ( $value instanceof SMWContainerValue ) { // recursive handling of containers
			$joinfield = "t$tableindex." . reset( array_keys( $proptable->objectfields ) ); // this must be a type 'p' object
			$proptables = SMWSQLStoreLight::getPropertyTables();
			foreach ( $value->getData()->getProperties() as $subproperty ) {
				$tableid = SMWSQLStoreLight::findPropertyTableID( $subproperty );
				if ( ( $tableid == '' ) && ( $value !== null ) ) { // maybe a type-polymorphic property like _1; use value to find type
					$tableid = SMWSQLStoreLight::findTypeTableID( reset( $value->getData()->getPropertyValues( $subproperty ) )->getTypeID() );
				}
				$subproptable = $proptables[$tableid];
				foreach ( $value->getData()->getPropertyValues( $subproperty ) as $subvalue ) {
					$tableindex++;
					if ( $subproptable->idsubject ) { // simply add property table to check values
						$from .= " INNER JOIN " . $db->tableName( $subproptable->name ) . " AS t$tableindex ON t$tableindex.s_id=$joinfield";
					} else { // exotic case with table that uses subject title+namespace in container object (should never happen in SMW core)
						$from .= " INNER JOIN " . $db->tableName( 'smw_ids' ) . " AS ids$tableindex ON ids$tableindex.smw_id=$joinfield" .
						         " INNER JOIN " . $db->tableName( $subproptable->name ) . " AS t$tableindex ON " .
						         "t$tableindex.s_title=ids$tableindex.smw_title AND t$tableindex.s_namespace=ids$tableindex.smw_namespace";
					}
					if ( $subproptable->fixedproperty == false ) { // the ID we get should be !=0, so no point in filtering the converse
						$where .= ( $where ? ' AND ':'' ) . "t$tableindex.p_id=" . $db->addQuotes( $this->getSMWPropertyID( $subproperty ) );
					}
					$this->prepareValueQuery( $from, $where, $subproptable, $subvalue, $tableindex );
				}
			}
		} elseif ( $value !== null ) { // add conditions for given value
			$dbkeys = $value->getDBkeys();
			$i = 0;
			foreach ( $proptable->objectfields as $fieldname => $typeid ) {
				if ( $i >= count( $dbkeys ) ) break;
				if ( $typeid == 'p' ) { // Special case: page id, resolve this in advance
					$oid = $this->getSMWPageID( $dbkeys[$i], $dbkeys[$i + 1], $dbkeys[$i + 2] );
					$i += 3; // skip these additional values (sortkey not needed here)
					$where .= ( $where ? ' AND ':'' ) . "t$tableindex.$fieldname=" . $db->addQuotes( $oid );
				} elseif ( $typeid != 'l' ) { // plain value, but not a text blob
					$where .= ( $where ? ' AND ':'' ) . "t$tableindex.$fieldname=" . $db->addQuotes( $dbkeys[$i] );
				}
				$i++;
			}
		}
	}

	public function getAllPropertySubjects( SMWPropertyValue $property, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getAllPropertySubjects (SMW)" );
		$result = $this->getPropertySubjects( $property, null, $requestoptions );
		wfProfileOut( "SMWSQLStoreLight::getAllPropertySubjects (SMW)" );
		return $result;
	}

	/**
	 * @todo Restrict this function to SMWWikiPageValue subjects.
	 */
	public function getProperties( $subject, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getProperties (SMW)" );
		$sid = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki() );
		if ( $sid == 0 ) { // no id, no page, no properties
			wfProfileOut( "SMWSQLStoreLight::getProperties (SMW)" );
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
		foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $proptable ) {
			$from = $db->tableName( $proptable->name );
			if ( $proptable->idsubject ) {
				$where = 's_id=' . $db->addQuotes( $sid );
			} elseif ( $subject->getInterwiki() == '' ) {
				$where = 's_title=' . $db->addQuotes( $subject->getDBkey() ) . ' AND s_namespace=' . $db->addQuotes( $subject->getNamespace() );
			} else { // subjects with non-emtpy interwiki cannot have properties
				continue;
			}
			if ( $proptable->fixedproperty == false ) { // select all properties
				$from .= " INNER JOIN " . $db->tableName( 'smw_ids' ) . " ON smw_id=p_id";
				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey', // select sortkey since it might be used in ordering (needed by Postgres)
				       $where . $this->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey' ),
					   'SMW::getProperties', $this->getSQLOptions( $suboptions, 'smw_sortkey' ) );
				while ( $row = $db->fetchObject( $res ) ) {
					$result[] = SMWPropertyValue::makeProperty( $row->smw_title );
				}
			} else { // just check if subject occurs in table
				$res = $db->select( $from, '*', $where, 'SMW::getProperties', array( 'LIMIT' => 1 ) );
				if ( $db->numRows( $res ) > 0 ) {
					$result[] = SMWPropertyValue::makeProperty( $proptable->fixedproperty );
				}
			}
			$db->freeResult( $res );
		}
		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStoreLight::getProperties (SMW)" );
		return $result;
	}

	/**
	 * Implementation of SMWStore::getInProperties(). This function is meant to
	 * be used for finding properties that link to wiki pages.
	 * @todo When used for other datatypes, the function may return too many
	 * properties since it selects results by comparing the stored information
	 * (DB keys) only, while not currently comparing the type of the returned
	 * property to the type of the queried data. So values with the same DB keys
	 * can be confused. This is a minor issue now since no code is known to use
	 * this function in cases where this occurs.
	 */
	public function getInProperties( SMWDataValue $value, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getInProperties (SMW)" );
		$db = wfGetDB( DB_SLAVE );
		$result = array();
		$typeid = $value->getTypeID();

		if ( $requestoptions !== null ) { // potentially need to get more results, since options apply to union
			$suboptions = clone $requestoptions;
			$suboptions->limit = $requestoptions->limit + $requestoptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}
		foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $proptable ) {
			if ( !$this->tableFitsType( $tid, $typeid ) ) continue;
			$select = $where = $from = '';
			if ( $proptable->fixedproperty == false ) { // join smw_ids to get property titles
				$from = $db->tableName( 'smw_ids' ) . " INNER JOIN " . $db->tableName( $proptable->name ) . " AS t1 ON t1.p_id=smw_id";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );
				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey', // select sortkey since it might be used in ordering (needed by Postgres)
									$where . $this->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey', $where != '' ),
									'SMW::getInProperties', $this->getSQLOptions( $suboptions, 'smw_sortkey' ) );
				while ( $row = $db->fetchObject( $res ) ) {
					$result[] = SMWPropertyValue::makeProperty( $row->smw_title );
				}
			} else {
				$from = $db->tableName( $proptable->name ) . " AS t1";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );
				$res = $db->select( $from, '*', $where, 'SMW::getProperties', array( 'LIMIT' => 1 ) );
				if ( $db->numRows( $res ) > 0 ) {
					$result[] = SMWPropertyValue::makeProperty( $proptable->fixedproperty );
				}
			}
			$db->freeResult( $res );
		}
		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStoreLight::getInProperties (SMW)" );
		return $result;
	}

///// Writing methods /////

	public function deleteSubject( Title $subject ) {
		wfProfileIn( 'SMWSQLStoreLight::deleteSubject (SMW)' );
		wfRunHooks( 'SMWSQLStoreLight::deleteSubjectBefore', array( $this, $subject ) );
		$this->deleteSemanticData( SMWWikiPageValue::makePageFromTitle( $subject ) );
		$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() ); // also delete redirects, may trigger update jobs!
		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = wfGetDB( DB_MASTER );
			$id = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), false );
			$db->delete( 'smw_conc2', array( 's_id' => $id ), 'SMW::deleteSubject::Conc2' );
			$db->delete( 'smw_conccache', array( 'o_id' => $id ), 'SMW::deleteSubject::Conccache' );
		}
		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
		///FIXME: clean internal caches here
		wfRunHooks( 'SMWSQLStoreLight::deleteSubjectAfter', array( $this, $subject ) );
		wfProfileOut( 'SMWSQLStoreLight::deleteSubject (SMW)' );
	}

	public function updateData( SMWSemanticData $data ) {
		wfProfileIn( "SMWSQLStoreLight::updateData (SMW)" );
		wfRunHooks( 'SMWSQLStoreLight::updateDataBefore', array( $this, $data ) );
		$subject = $data->getSubject();
		$this->deleteSemanticData( $subject );
		$redirects = $data->getPropertyValues( SMWPropertyValue::makeProperty( '_REDI' ) );
		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace(), $redirect->getDBkey(), $redirect->getNameSpace() );
			wfProfileOut( "SMWSQLStoreLight::updateData (SMW)" );
			return; // stop here -- no support for annotations on redirect pages!
		} else {
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() );
		}
		// always make an ID (pages without ID cannot be in query results, not even in fixed value queries!):
		$sid = $this->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(), '', true, $subject->getSortkey() );
		$updates = array(); // collect data for bulk updates; format: tableid => updatearray
		$this->prepareDBUpdates( $updates, $data, $sid );

		$db = wfGetDB( DB_MASTER );
		foreach ( $updates as $tablename => $uvals ) {
 			$db->insert( $tablename, $uvals, "SMW::updateData$tablename" );
		}

		// Concepts are not just written but carefully updated,
		// preserving existing metadata (cache ...) for a concept:
		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) {
			$property = SMWPropertyValue::makeProperty( '_CONC' );
			$concept_desc = end( $data->getPropertyValues( $property ) );
			if ( ( $concept_desc !== null ) && ( $concept_desc->isValid() ) )  {
				$up_conc2 = array(
				     'concept_txt'   => $concept_desc->getConceptText(),
				     'concept_docu'  => $concept_desc->getDocu(),
				     'concept_features' => $concept_desc->getQueryFeatures(),
				     'concept_size'  => $concept_desc->getSize(),
				     'concept_depth' => $concept_desc->getDepth()
				);
			} else {
				$up_conc2 = array(
				     'concept_txt'   => '',
				     'concept_docu'  => '',
				     'concept_features' => 0,
				     'concept_size'  => - 1,
				     'concept_depth' => - 1
				);
			}
			$row = $db->selectRow(
				'smw_conc2',
				array( 'cache_date', 'cache_count' ),
				array( 's_id' => $sid ),
				'SMWSQLStore2Queries::updateConst2Data'
			);
			if ( ( $row === false ) && ( $up_conc2['concept_txt'] != '' ) ) { // insert newly given data
				$up_conc2['s_id'] = $sid;
				$db->insert( 'smw_conc2', $up_conc2, 'SMW::updateConc2Data' );
			} elseif ( $row !== false ) { // update data, preserve existing entries
				$db->update( 'smw_conc2', $up_conc2, array( 's_id' => $sid ), 'SMW::updateConc2Data' );
			}
		}

		// Finally update caches (may be important if jobs are directly following this call)
		$this->m_semdata[$sid] = clone $data;
		$this->m_sdstate[$sid] = array_keys( SMWSQLStoreLight::getPropertyTables() ); // everything that one can know
		wfRunHooks( 'SMWSQLStoreLight::updateDataAfter', array( $this, $data ) );
		wfProfileOut( "SMWSQLStoreLight::updateData (SMW)" );
	}

	/**
	 * Extend the given update array to account for the data in the SMWSemanticData object.
	 * The subject page of the data container is ignored, and the given $pageid is used directly.
	 * However, if the subject is empty, then a blank node (internal id) is generated instead
	 * of using the given $pageid directly (note that internal objects always belong to one
	 * proper object which in this case is the given $pageid).
	 *
	 * The function returns the id that was used for writing. Especially, any newly created
	 * internal id is returned.
	 */
	protected function prepareDBUpdates( &$updates, SMWSemanticData $data, $pageid ) {
		$subject = $data->getSubject();
		$sid = ( $subject !== null ) ? $pageid:$this->makeSMWBnodeID( $pageid );
		$proptables = SMWSQLStoreLight::getPropertyTables();
		foreach ( $data->getProperties() as $property ) {
			$tableid = SMWSQLStoreLight::findPropertyTableID( $property );
			if ( !$tableid ) { // happens when table is not determined by property; use values to find type
				$dv = reset( $data->getPropertyValues( $property ) );
				$tableid = SMWSQLStoreLight::findTypeTableID( $dv->getTypeID() );
			}
			if ( !$tableid ) { // can't store this data, sorry
				return $sid;
			}
			$proptable = $proptables[$tableid];
			foreach ( $data->getPropertyValues( $property ) as $dv ) {
				if ( !$dv->isValid() || ( $tableid == 'smw_redi2' ) ) continue;
				    // errors are already recorded separately, no need to store them here;
				    // redirects were treated above
				///TODO check needed if subject is null (would happen if a user defined proptable with !idsubject was used on an internal object -- currently this is not possible
				$uvals = ( $proptable->idsubject ) ? array( 's_id' => $sid ):
							array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() );
				if ( $proptable->fixedproperty == false ) {
					$uvals['p_id'] = $this->makeSMWPropertyID( $property );
				}
				if ( $dv instanceof SMWContainerValue ) { // process subobjects recursively
					$bnode = $this->prepareDBUpdates( $updates, $dv->getData(), $pageid );
					// Note: tables for container objects MUST have objectfields == array(<somename> => 'p')
					reset( $proptable->objectfields );
					$uvals[key( $proptable->objectfields )] = $bnode;
				} else {
					$dbkeys = $dv->getDBkeys();
					reset( $dbkeys );
					foreach ( $proptable->objectfields as $fieldname => $typeid ) {
						if ( $typeid != 'p' ) {
							$uvals[$fieldname] = current( $dbkeys );
						} else {
							$title = current( $dbkeys );
							$namespace = next( $dbkeys );
							$iw = next( $dbkeys );
							$sortkey = next( $dbkeys ); // not used; sortkeys are not set on writing objects
							$uvals[$fieldname] = $this->makeSMWPageID( $title, $namespace, $iw );
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
	 * @todo Currently the sortkey is not moved with the remaining data. It is
	 * not possible to move it reliably in all cases: we cannot distinguish an
	 * unset sortkey from one that was set to the name of oldtitle. Maybe use
	 * update jobs right away?
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		global $smwgQEqualitySupport;
		wfProfileIn( "SMWSQLStoreLight::changeTitle (SMW)" );
		// get IDs but do not resolve redirects:
		$sid = $this->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', false );
		$tid = $this->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', false );
		$db = wfGetDB( DB_MASTER );

		if ( ( $tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // target not used anywhere yet, just hijack its title for our current id
			// This condition may not hold even if $newtitle is currently unused/non-existing since we keep old IDs.
			// If equality support is off, then this simple move does too much; fall back to general case below.
			if ( $sid != 0 ) { // change id entry to refer to the new title
				$db->update( 'smw_ids', array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
				            array( 'smw_id' => $sid ), 'SMWSQLStoreLight::changeTitle' );
			} else { // make new (target) id for use in redirect table
				$sid = $this->makeSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '' );
			} // at this point, $sid is the id of the target page (according to smw_ids)
			$this->makeSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), SMW_SQL2_SMWREDIIW ); // make redirect id for oldtitle
			$db->insert( 'smw_redi2', array( 's_title' => $oldtitle->getDBkey(), 's_namespace' => $oldtitle->getNamespace(), 'o_id' => $sid ),
			             'SMWSQLStoreLight::changeTitle' );
			$this->m_ids[" " . $oldtitle->getNamespace() . " " . $oldtitle->getDBkey() . " C"] = $sid;
			// $this->m_ids[" " . $oldtitle->getNamespace() . " " . $oldtitle->getDBkey() . " -"] = Already OK after makeSMWPageID above
			$this->m_ids[" " . $newtitle->getNamespace() . " " . $newtitle->getDBkey() . " C"] = $sid;
			$this->m_ids[" " . $newtitle->getNamespace() . " " . $newtitle->getDBkey() . " -"] = $sid;
			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behaviour: everything will continue to work until the existing redirects are updated,
			/// which will hopefully be done to fix the double redirect.
		} else { // general move method that should be correct in all cases (equality support respected when updating redirects)
			// delete any existing data from new title:
			$this->deleteSemanticData( SMWWikiPageValue::makePageFromTitle( $newtitle ) ); // $newtitle should not have data, but let's be sure
			$this->updateRedirects( $newtitle->getDBkey(), $newtitle->getNamespace() ); // may trigger update jobs!
			// move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->changeSMWPageID( $sid, $tid, $oldtitle->getNamespace(), $newtitle->getNamespace(), true, false );
			}
			// now write a redirect from old title to new one; this also updates references in other tables as needed
			$this->updateRedirects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
		}
		wfProfileOut( "SMWSQLStoreLight::changeTitle (SMW)" );
	}

///// Query answering /////

	function getQueryResult( SMWQuery $query ) {
		return null; // not supported by this store
	}

///// Special page functions /////

	public function getPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getStatistics() {
		return array('PROPUSES' => 0, 'USEDPROPS' => 0, 'DECLPROPS' => 0 ); // not supported by this store
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
	 * Create required SQL tables. This function also performs upgrades of table contents
	 * when required.
	 *
	 * Documentation for the table smw_ids: This table is normally used to store references to wiki
	 * pages (possibly with some external interwiki prefix). There are, however, some special objects
	 * that are also stored therein. These are marked by special interwiki prefixes (iw) that cannot
	 * occcur in real life:
	 *
	 * - Rows with iw SMW_SQL2_SMWIW describe "virtual" objects that have no page or other reference in the wiki.
	 * These are specifically the auxilliary objects ("bnodes") required to encode multi-valued properties,
     * which are recognised by their empty title field. As a namespace, they use the id of the object that
	 * "owns" them, so that the can be reused/maintained more easily.
	 * A second object type that can occur in SMW_SQL2_SMWIW rows are the internal properties used to
	 * refer to some position in a multivalued property value. They have titles like "1", "2", "3", ...
	 * and occur only once (i.e. there is just one such property for the whoel wiki, and it has no type).
	 * The namespace of those entries is the usual property namespace.
	 *
	 * - Rows with iw SMW_SQL2_SMWREDIIW are similar to normal entries for (internal) wiki pages, but the iw
	 * indicates that the page is a redirect, the target of which should be sought using the smw_redi2 table.
	 *
	 * - The (unique) row with iw SMW_SQL2_SMWBORDERIW just marks the border between predefined ids (rows that
	 * are reserved for hardcoded ids built into SMW) and normal entries. It is no object, but makes sure that
	 * SQL's auto increment counter is high enough to not add any objects before that marked "border".
	 */
	protected function setupTables( $verbose, $db ) {
		global $wgDBtype;
		
		$reportTo = $verbose ? $this : null; // Use $this to report back from static SMWSQLHelpers.
		
		// Repeatedly used DB field types defined here for convenience.
		$dbtypes = array(
			't' => SMWSQLHelpers::getStandardDBType( 'title' ),
			'u' => ( $wgDBtype == 'postgres' ? 'TEXT' : 'VARCHAR(63) binary' ),
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
		if ( ( $db->tableExists( $smw_spec2 ) ) && ( $db->fieldExists( $smw_spec2, 'sp_id', 'SMWSQLStore2::setup' ) ) ) {
			if ( $wgDBtype == 'postgres' ) {
				$db->query( "ALTER TABLE $smw_spec2 ALTER COLUMN sp_id RENAME TO p_id", 'SMWSQLStore2::setup' );
			} else {
				$db->query( "ALTER TABLE $smw_spec2 CHANGE `sp_id` `p_id` " . $dbtypes['p'] . " NOT NULL", 'SMWSQLStore2::setup' );
			}
		}

		// Set up table for internal IDs used in this store:
		SMWSQLHelpers::setupTable(
			'smw_ids',
			array(
				'smw_id' => $dbtypes['p'] . ' NOT NULL' . ( $wgDBtype == 'postgres' ? ' PRIMARY KEY' : ' KEY AUTO_INCREMENT' ),
				'smw_namespace' => $dbtypes['n'] . ' NOT NULL',
				'smw_title' => $dbtypes['t'] . ' NOT NULL',
				'smw_iw' => $dbtypes['w'],
				'smw_sortkey' => $dbtypes['t']  . ' NOT NULL'
			),
			$db,
			$reportTo
		);
		
		SMWSQLHelpers::setupIndex( 'smw_ids', array( 'smw_id', 'smw_title,smw_namespace,smw_iw', 'smw_sortkey' ), $db );

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
		
		// Set up concept descriptions.
		SMWSQLHelpers::setupTable(
			'smw_conc2',
			array(
				's_id' => $dbtypes['p'] . ' NOT NULL' . ( $wgDBtype == 'postgres' ? ' PRIMARY KEY' : ' KEY' ),
				'concept_txt' => $dbtypes['l'],
				'concept_docu' => $dbtypes['l'],
				'concept_features' => $dbtypes['i'],
				'concept_size' => $dbtypes['i'],
				'concept_depth' => $dbtypes['i'],
				'cache_date' => $dbtypes['j'],
				'cache_count' => $dbtypes['j']
			),
			$db,
			$reportTo
		);
		
		SMWSQLHelpers::setupIndex( 'smw_conc2', array( 's_id' ), $db );

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
			$this->reportProgress( "   ... allocate space for internal properties\n", $verbose );
			$this->moveSMWPageID( 50 ); // make sure position 50 is empty
			$db->insert( 'smw_ids', array(
					'smw_id' => 50,
					'smw_title' => '',
					'smw_namespace' => 0,
					'smw_iw' => SMW_SQL2_SMWBORDERIW,
					'smw_sortkey' => ''
				), 'SMW::setup'
			); // put dummy "border element" on index 50

			$this->reportProgress( "   ", $verbose );
			for ( $i = 0; $i < 50; $i++ ) { // make way for built-in ids
				$this->moveSMWPageID( $i );
				$this->reportProgress( ".", $verbose );
			}
			$this->reportProgress( "done\n", $verbose );
		} else {
			$this->reportProgress( "   ... space for internal properties already allocated.\n", $verbose );
		}
		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->reportProgress( "   ... writing entries for internal properties.\n", $verbose );
		foreach ( SMWSQLStoreLight::$special_ids as $prop => $id ) {
			$p = SMWPropertyValue::makeProperty( $prop );
			$db->replace( 'smw_ids',	array( 'smw_id' ), array(
					'smw_id' => $id,
					'smw_title' => $p->getDBkey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->getPropertyInterwiki( $p ),
					'smw_sortkey' => $p->getDBkey()
				), 'SMW::setup'
			);
		}
		if ( $wgDBtype == 'postgres' ) {
			$this->reportProgress( "   ... updating smw_ids_smw_id_seq sequence accordingly.\n", $verbose );
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
		$tables = array( 'smw_ids', 'smw_conc2', 'smw_conccache' );
		foreach ( SMWSQLStoreLight::getPropertyTables() as $proptable ) {
			$tables[] = $proptable->name;
		}
		foreach ( $tables as $table ) {
			$name = $db->tableName( $table );
			$db->query( 'DROP TABLE' . ( $wgDBtype == 'postgres' ? '':' IF EXISTS' ) . $name, 'SMWSQLStoreLight::drop' );
			$this->reportProgress( " ... dropped table $name.\n", $verbose );
		}
		$this->reportProgress( "All data removed successfully.\n", $verbose );
		return true;
	}

	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		$updatejobs = array();
		$emptyrange = true; // was nothing found in this run?

		// update by MediaWiki page id --> make sure we get all pages
		$tids = array();
		for ( $i = $index; $i < $index + $count; $i++ ) { // array of ids
			$tids[] = $i;
		}
		$titles = Title::newFromIDs( $tids );
		foreach ( $titles as $title ) {
			// set $wgTitle, in case semantic data is set based
			// on values not originating from the page (such as
			// via the External Data extension)
			global $wgTitle;
			$wgTitle = $title;
			if ( ( $namespaces == false ) || ( in_array( $title->getNamespace(), $namespaces ) ) ) {
				$updatejobs[] = new SMWUpdateJob( $title );
				$emptyrange = false;
			}
		}

		// update by internal SMW id --> make sure we get all objects in SMW
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( 'smw_ids', array( 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw' ),
		                   "smw_id >= $index AND smw_id < " . $db->addQuotes( $index + $count ), __METHOD__ );
		foreach ( $res as $row ) {
			$emptyrange = false; // note this even if no jobs were created
			if ( ( $namespaces != false ) && ( !in_array( $row->smw_namespace, $namespaces ) ) ) continue;
			if ( ( $row->smw_iw == '' ) || ( $row->smw_iw == SMW_SQL2_SMWREDIIW ) ) { // objects representing pages in the wiki, even special pages
				// TODO: special treament of redirects needed, since the store will not act on redirects that did not change according to its records
				$title = Title::makeTitle( $row->smw_namespace, $row->smw_title );
				if ( !$title->exists() ) {
					$updatejobs[] = new SMWUpdateJob( $title );
				}
			} elseif ( $row->smw_iw { 0 } != ':' ) { // refresh all "normal" interwiki pages by just clearing their content
				$this->deleteSemanticData( SMWWikiPageValue::makePage( $row->smw_namespace, $row->smw_title, '', $row->smw_iw ) );
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
			$nextpos = ( ( $next2 != 0 ) && ( $next2 < $next1 ) ) ? $next2:$next1;
		}
		$max1 = $db->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$max2 = $db->selectField( 'smw_ids', 'MAX(smw_id)', '', __METHOD__ );
		$index = $nextpos ? $nextpos: - 1;
		return ( $index > 0 ) ? $index / max( $max1, $max2 ) : 1;
	}


///// Concept caching /////

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function refreshConceptCache( $concept ) {
		wfProfileIn( 'SMWSQLStoreLight::refreshConceptCache (SMW)' );
		global $smwgIP;
		include_once( "$smwgIP/includes/storage/SMW_SQLStore2_Queries.php" );
		$qe = new SMWSQLStore2QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->refreshConceptCache( $concept );
		wfProfileOut( 'SMWSQLStoreLight::refreshConceptCache (SMW)' );
		return $result;
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache( $concept ) {
		wfProfileIn( 'SMWSQLStoreLight::deleteConceptCache (SMW)' );
		global $smwgIP;
		include_once( "$smwgIP/includes/storage/SMW_SQLStore2_Queries.php" );
		$qe = new SMWSQLStore2QueryEngine( $this, wfGetDB( DB_MASTER ) );
		$result = $qe->deleteConceptCache( $concept );
		wfProfileOut( 'SMWSQLStoreLight::deleteConceptCache (SMW)' );
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
		wfProfileIn( 'SMWSQLStoreLight::getConceptCacheStatus (SMW)' );
		$db = wfGetDB( DB_SLAVE );
		$cid = $this->getSMWPageID( $concept->getDBkey(), $concept->getNamespace(), '', false );
		$row = $db->selectRow( 'smw_conc2',
		         array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date', 'cache_count' ),
		         array( 's_id' => $cid ), 'SMWSQLStoreLight::getConceptCacheStatus (SMW)' );
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
		wfProfileOut( 'SMWSQLStoreLight::getConceptCacheStatus (SMW)' );
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
			if ( ( $valuecol != '' ) && ( $requestoptions->sort ) ) {
				$sql_options['ORDER BY'] = $requestoptions->ascending ? $valuecol : $valuecol . ' DESC';
			}
		}
		return $sql_options;
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL conditions.
	 * The parameter $valuecol defines the string name of the column to which
	 * value restrictions etc. are to be applied.
	 * @param $requestoptions object with options
	 * @param $valuecol name of SQL column to which conditions apply
	 * @param $labelcol name of SQL column to which string conditions apply, if any
	 * @param $addand Boolean to indicate whether the string should begin with " AND " if non-empty
	 */
	protected function getSQLConditions( $requestoptions, $valuecol = '', $labelcol = '', $addand = true ) {
		$sql_conds = '';
		if ( $requestoptions !== null ) {
			$db = wfGetDB( DB_SLAVE ); /// TODO avoid doing this here again, all callers should have one
			if ( ( $valuecol != '' ) && ( $requestoptions->boundary !== null ) ) { // apply value boundary
				if ( $requestoptions->ascending ) {
					$op = $requestoptions->include_boundary ? ' >= ':' > ';
				} else {
					$op = $requestoptions->include_boundary ? ' <= ':' < ';
				}
				$sql_conds .= ( $addand ? ' AND ':'' ) . $valuecol . $op . $db->addQuotes( $requestoptions->boundary );
			}
			if ( $labelcol != '' ) { // apply string conditions
				foreach ( $requestoptions->getStringConditions() as $strcond ) {
					$string = str_replace( '_', '\_', $strcond->string );
					switch ( $strcond->condition ) {
						case SMWStringCondition::STRCOND_PRE:  $string .= '%'; break;
						case SMWStringCondition::STRCOND_POST: $string = '%' . $string; break;
						case SMWStringCondition::STRCOND_MID:  $string = '%' . $string . '%'; break;
					}
					$sql_conds .= ( ( $addand || ( $sql_conds != '' ) ) ? ' AND ':'' ) . $labelcol . ' LIKE ' . $db->addQuotes( $string );
				}
			}
		}
		return $sql_conds;
	}

	/**
	 * Not in all cases can requestoptions be forwarded to the DB using
	 * getSQLConditions() and getSQLOptions(): some data comes from caches that
	 * do not respect the options yet. This method takes an array of results
	 * (SMWDataValue objects) *of the same type* and applies the given
	 * requestoptions as appropriate.
	 */
	protected function applyRequestOptions( $data, $requestoptions ) {
		wfProfileIn( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
		if ( ( count( $data ) == 0 ) || ( $requestoptions === null ) ) {
			wfProfileOut( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
			return $data;
		}
		$result = array();
		$sortres = array();
		list( $sig, $valueindex, $labelindex ) = SMWSQLStoreLight::getTypeSignature( reset( $data )->getTypeID() );
		$numeric = ( ( $valueindex >= 0 ) && ( strlen( $sig ) > $valueindex ) &&
		             ( ( $sig { $valueindex } != 'f' ) || ( $sig { $valueindex } != 'n' ) ) );
		$i = 0;
		foreach ( $data as $item ) {
			$ok = true; // keep datavalue only if this remains true
			$keys = $item->getDBkeys();
			$value = array_key_exists( $valueindex, $keys ) ? $keys[$valueindex]:'';
			$label = array_key_exists( $labelindex, $keys ) ? $keys[$labelindex]:'';
			if ( $requestoptions->boundary !== null ) { // apply value boundary
				$strc = $numeric ? 0:strcmp( $value, $requestoptions->boundary );
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
				$sortres[$i] = $value; // we cannot use $value as key: it is not unique if there are units!
				$i++;
			}
		}
		if ( $requestoptions->sort ) {
			$flag = $numeric ? SORT_NUMERIC:SORT_LOCALE_STRING;
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
		wfProfileOut( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
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
	 * the index of the label label field. These entries correspond to
	 * the results of SMWDataValue::getSignature(),
	 * SMWDatavalue::getValueIndex(), and SMWDatavalue::getLabelIndexes().
	 * @todo Custom unit types (SMWLinearValue) have page names as their
	 * type id and are not in the array cache. Can we still determine their
	 * signature without creating them?
	 */
	public static function getTypeSignature( $typeid ) {
		if ( !array_key_exists( $typeid, self::$type_signatures ) ) {
			$dv = SMWDataValueFactory::newTypeIDValue( $typeid );
			self::$type_signatures[$typeid] = array( $dv->getSignature(), $dv->getValueIndex(), $dv->getLabelIndex() );
		}
		return self::$type_signatures[$typeid];
	}

	/**
	 * Check if the given table can be used to store values of the given
	 * signature, where $signature is as returned by getTypeSignature().
	 * @todo Maybe rather use SMWSQLStore2Table object as parameter.
	 */
	public static function tableFitsSignature( $tableid, $signature ) {
		$proptables = SMWSQLStoreLight::getPropertyTables();
		$tablesig = str_replace( 'p', 'tnwt', $proptables[$tableid]->getFieldSignature() ); // expand internal page type to single fields
		$valuesig = reset( $signature );
		return ( $valuesig == substr( $tablesig, 0, strlen( $valuesig ) ) );
	}

	/**
	 * Check if the given table can be used to store values of the given
	 * type.
	 */
	public static function tableFitsType( $tableid, $typeid ) {
		return SMWSQLStoreLight::tableFitsSignature( $tableid, SMWSQLStoreLight::getTypeSignature( $typeid ) );
	}

	/**
	 * Find the id of a property table that is suitable for storing values of
	 * the given type. The type is specified by an SMW type id such as '_wpg'.
	 * An empty string is returned if no matching table could be found.
	 */
	public static function findTypeTableID( $typeid ) {
		if ( !array_key_exists( $typeid, SMWSQLStoreLight::$property_table_ids ) ) {
			$signature = SMWSQLStoreLight::getTypeSignature( $typeid );
			foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $proptable ) {
				if ( SMWSQLStoreLight::tableFitsSignature( $tid, $signature ) ) {
					SMWSQLStoreLight::$property_table_ids[$typeid] = $tid;
					return $tid;
				}
			}
			SMWSQLStoreLight::$property_table_ids[$typeid] = ''; // no matching table found
		}
		return SMWSQLStoreLight::$property_table_ids[$typeid];
	}

	/**
	 * Retrieve the id of the property table that is to be used for storing
	 * values for the given property object.
	 */
	public static function findPropertyTableID( $property ) {
		if ( SMWSQLStoreLight::$fixed_prop_tables === null ) { // build lookup array once
			SMWSQLStoreLight::$fixed_prop_tables = array();
			foreach ( SMWSQLStoreLight::getPropertyTables() as $tid => $proptable ) {
				if ( $proptable->fixedproperty != false ) {
					SMWSQLStoreLight::$fixed_prop_tables[$proptable->fixedproperty] = $tid;
				}
			}
		}
		$propertykey = ( $property->isUserDefined() ) ? $property->getDBkey():$property->getPropertyId();
		if ( array_key_exists( $propertykey, SMWSQLStoreLight::$fixed_prop_tables ) ) {
			$signature = SMWSQLStoreLight::getTypeSignature( $property->getPropertyTypeID() );
			if ( SMWSQLStoreLight::tableFitsSignature( SMWSQLStoreLight::$fixed_prop_tables[$propertykey], $signature ) )
				return SMWSQLStoreLight::$fixed_prop_tables[$propertykey];
		} // else: don't check for non-fitting entries in $fixed_prop_tables: not really important

		return SMWSQLStoreLight::findTypeTableID( $property->getPropertyTypeID() );
	}

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find
	 * the canonical alias ID for the given page. If no such ID exists, 0 is
	 * returned.
	 */
	public function getSMWPageID( $title, $namespace, $iw, $canonical = true ) {
		$sort = '';
		return $this->getSMWPageIDandSort( $title, $namespace, $iw, $sort, $canonical );
	}

	/**
	 * Like getSMWPageID(), but also sets the Call-By-Ref parameter $sort to
	 * the current sortkey.
	 * @todo Ensuring that properties redirect to properties only should not be done here.
	 * @todo Centralise creation of id cache keys, and make sure non-local pages have only one key
	 * (no need to distniguish canonical/non-canonical in this case).
	 */
	public function getSMWPageIDandSort( $title, $namespace, $iw, &$sort, $canonical ) {
		global $smwgQEqualitySupport;
		wfProfileIn( 'SMWSQLStoreLight::getSMWPageID (SMW)' );
		$ckey = "$iw $namespace $title C";
		$nkey = "$iw $namespace $title -";
		$key = ( $canonical ? $ckey:$nkey );
		if ( array_key_exists( $key, $this->m_ids ) ) {
			wfProfileOut( 'SMWSQLStoreLight::getSMWPageID (SMW)' );
			return $this->m_ids[$key];
		}
		if ( count( $this->m_ids ) > 1500 ) { // prevent memory leak in very long PHP runs
			$this->m_ids = array();
		}
		$db = wfGetDB( DB_SLAVE );
		$id = 0;
		if ( $iw != '' ) { // external page; no need to think about redirects
			$res = $db->select( 'smw_ids', array( 'smw_id', 'smw_sortkey' ),
			                   array( 'smw_title' => $title, 'smw_namespace' => $namespace, 'smw_iw' => $iw ),
			                   'SMW::getSMWPageID', array( 'LIMIT' => 1 ) );
			if ( $row = $db->fetchObject( $res ) ) {
				$id = $row->smw_id;
				$sort = $row->smw_sortkey;
			}
			$this->m_ids[ $canonical ? $nkey:$ckey ] = $id; // unique id, make sure cache for canonical+non-cacnonical gets filled
		} else { // check for potential redirects also
			$res = $db->select( 'smw_ids', array( 'smw_id', 'smw_iw', 'smw_sortkey' ),
			         'smw_title=' . $db->addQuotes( $title ) . ' AND smw_namespace=' . $db->addQuotes( $namespace ) .
			         ' AND (smw_iw=' . $db->addQuotes( '' ) . ' OR smw_iw=' . $db->addQuotes( SMW_SQL2_SMWREDIIW ) . ')',
			         'SMW::getSMWPageID', array( 'LIMIT' => 1 ) );
			if ( $row = $db->fetchObject( $res ) ) {
				$id = $row->smw_id; // set id in any case, the below check for properties will use even the redirect id in emergency
				$sort = $row->smw_sortkey;
				if ( ( $row->smw_iw == '' ) ) { // the id found is unique (canonical and non-canonical); fill cache also for the case *not* asked for
					$this->m_ids[ $canonical ? $nkey:$ckey ] = $id; // (the other cache is filled below)
				} elseif ( $canonical && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // get redirect alias
					if ( $namespace == SMW_NS_PROPERTY ) { // redirect properties only to properties
						///TODO: Shouldn't this condition be ensured during writing?
						$res2 = $db->select( array( 'smw_redi2', 'smw_ids' ), 'o_id',
							'o_id=smw_id AND smw_namespace=s_namespace AND s_title=' . $db->addQuotes( $title ) .
							' AND s_namespace=' . $db->addQuotes( $namespace ), 'SMW::getSMWPageID', array( 'LIMIT' => 1 ) );
					} else {
						$res2 = $db->select( 'smw_redi2', 'o_id',
							's_title=' . $db->addQuotes( $title ) . ' AND s_namespace=' . $db->addQuotes( $namespace ),
							'SMW::getSMWPageID', array( 'LIMIT' => 1 ) );
					}
					if ( $row = $db->fetchObject( $res2 ) ) {
						$id = $row->o_id;
					}
					$db->freeResult( $res2 );
				}
			}
		}
		$db->freeResult( $res );

		$this->m_ids[$key] = $id;
		wfProfileOut( 'SMWSQLStoreLight::getSMWPageID (SMW)' );
		return $id;
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
	protected function makeSMWPageID( $title, $namespace, $iw, $canonical = true, $sortkey = '' ) {
		wfProfileIn( 'SMWSQLStoreLight::makeSMWPageID (SMW)' );
		$oldsort = '';
		$id = $this->getSMWPageIDandSort( $title, $namespace, $iw, $oldsort, $canonical );
		if ( $id == 0 ) {
			$db = wfGetDB( DB_MASTER );
			$sortkey = $sortkey ? $sortkey:( str_replace( '_', ' ', $title ) );
			$db->insert( 'smw_ids',
			            array( 'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
			                   'smw_title' => $title,
			                   'smw_namespace' => $namespace,
			                   'smw_iw' => $iw,
			                   'smw_sortkey' => $sortkey ), 'SMW::makeSMWPageID' );
			$id = $db->insertId();
			$this->m_ids["$iw $namespace $title -"] = $id; // fill that cache, even if canonical was given
			// This ID is also authorative for the canonical version.
			// This is always the case: if $canonical===false and $id===0, then there is no redi-entry in
			// smw_ids either, hence the object just did not exist at all.
			$this->m_ids["$iw $namespace $title C"] = $id;
		} elseif ( ( $sortkey != '' ) && ( $sortkey != $oldsort ) ) {
			$db = wfGetDB( DB_MASTER );
			$db->update( 'smw_ids', array( 'smw_sortkey' => $sortkey ), array( 'smw_id' => $id ), 'SMW::makeSMWPageID' );
		}
		wfProfileOut( 'SMWSQLStoreLight::makeSMWPageID (SMW)' );
		return $id;
	}

	/**
	 * Properties have a mechanisms for being predefined (i.e. in PHP instead
	 * of in wiki). Special "interwiki" prefixes separate the ids of such
	 * predefined properties from the ids for the current pages (which may,
	 * e.g. be moved, while the predefined object is not movable).
	 */
	private function getPropertyInterwiki( SMWPropertyValue $property ) {
		if ( $property->isUserDefined() ) {
			return '';
		} else {
			return $property->isVisible() ? SMW_SQL2_SMWPREDEFIW:SMW_SQL2_SMWINTDEFIW;
		}
	}

	/**
	 * This function does the same as getSMWPageID() but takes into account
	 * that properties might be predefined.
	 */
	public function getSMWPropertyID( SMWPropertyValue $property ) {
		if ( ( !$property->isUserDefined() ) && ( array_key_exists( $property->getPropertyID(), SMWSQLStoreLight::$special_ids ) ) ) {
			return SMWSQLStoreLight::$special_ids[$property->getPropertyID()]; // very important property with fixed id
		} else {
			return $this->getSMWPageID( $property->getDBkey(), SMW_NS_PROPERTY, $this->getPropertyInterwiki( $property ), true );
		}
	}

	/**
	 * This function does the same as makeSMWPageID() but takes into account
	 * that properties might be predefined.
	 */
	protected function makeSMWPropertyID( SMWPropertyValue $property ) {
		if ( ( !$property->isUserDefined() ) && ( array_key_exists( $property->getPropertyID(), SMWSQLStoreLight::$special_ids ) ) ) {
			return SMWSQLStoreLight::$special_ids[$property->getPropertyID()]; // very important property with fixed id
		} else {
			return $this->makeSMWPageID( $property->getDBkey(), SMW_NS_PROPERTY, $this->getPropertyInterwiki( $property ), true );
		}
	}

	/**
	 * Extend the ID cache as specified. This is called in places where IDs are
	 * retrieved by SQL queries and it would be a pity to throw them away. This
	 * function expects to get the contents of a row in smw_ids, i.e. possibly
	 * with iw being SMW_SQL2_SMWREDIIW. This information is used to determine
	 * whether the given ID is canonical or not.
	 */
	public function cacheSMWPageID( $id, $title, $namespace, $iw ) {
		$ckey = "$iw $namespace $title C";
		$nkey = "$iw $namespace $title -";
		if ( count( $this->m_ids ) > 1500 ) { // prevent memory leak in very long PHP runs
			$this->m_ids = array();
		}
		$this->m_ids[$nkey] = $id;
		if ( $iw != SMW_SQL2_SMWREDIIW ) {
			$this->m_ids[$ckey] = $id;
		}
	}

	/**
	 * Get a numeric ID for some Bnode ("internal object") that is to be used
	 * to encode a container property value. Bnodes are managed through the
	 * smw_ids table but will always have an empty smw_title, and smw_namespace
	 * being set to the parent object (the id of the page that uses the Bnode).
	 * Unused Bnodes are not deleted but marked as available by setting
	 * smw_namespace to 0. This method then tries to reuse an unused bnode
	 * before making a new one.
	 * @note Every call to this function, even if the same parameter id is
	 * used, returns a new bnode id!
	 */
	protected function makeSMWBnodeID( $sid ) {
		$db = wfGetDB( DB_MASTER );
		// check if there is an unused bnode to take:
		$res = $db->select(	'smw_ids', 'smw_id', array( 'smw_title' => '', 'smw_namespace' => 0, 'smw_iw' => SMW_SQL2_SMWIW ),
			                'SMW::makeSMWBnodeID', array( 'LIMIT' => 1 ) );
		$id = ( $row = $db->fetchObject( $res ) ) ? $row->smw_id:0;
		$db->freeResult( $res );
		// claim that bnode:
		if ( $id != 0 ) {
			$db->update( 'smw_ids',	array( 'smw_namespace' => $sid ),
			             array( 'smw_id' => $id,
			                    'smw_title' => '',
			                    'smw_namespace' => 0,
			                    'smw_iw' => SMW_SQL2_SMWIW ), 'SMW::makeSMWBnodeID',	array( 'LIMIT' => 1 )	);
			if ( $db->affectedRows() == 0 ) { // Oops, someone was faster (collisions are possible here, no locks)
				$id = 0; // fallback: make a new node (TODO: we could also repeat to try another ID)
			}
		}
		// if no node was found yet, make a new one:
		if ( $id == 0 ) {
			$db->insert( 'smw_ids',
			             array( 'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
			                    'smw_title' => '',
			                    'smw_namespace' => $sid,
			                    'smw_iw' => SMW_SQL2_SMWIW ), 'SMW::makeSMWBnodeID' );
			$id = $db->insertId();
		}
		return $id;
	}

	/**
	 * Change an internal id to another value. If no target value is given, the
	 * value is changed to become the last id entry (based on the automatic id
	 * increment of the database). Whatever currently occupies this id will be
	 * moved consistently in all relevant tables. Whatever currently occupies
	 * the target id will be ignored (it should be ensured that nothing is
	 * moved to an id that is still in use somewhere).
	 * @note This page does not update any caches. If relevant, this needs to
	 * be effected by the caller.
	 */
	protected function moveSMWPageID( $curid, $targetid = 0 ) {
		$db = wfGetDB( DB_MASTER );
		$row = $db->selectRow( 'smw_ids',
		                       array( 'smw_id', 'smw_namespace', 'smw_title', 'smw_iw', 'smw_sortkey' ),
		                       array( 'smw_id' => $curid ),	'SMWSQLStoreLight::moveSMWPageID' );
		if ( $row === false ) return; // no id at current position, ignore
		if ( $targetid == 0 ) { // append new id
			$db->insert( 'smw_ids', array( 'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
			                              'smw_title' => $row->smw_title,
			                              'smw_namespace' => $row->smw_namespace,
			                              'smw_iw' => $row->smw_iw,
			                              'smw_sortkey' => $row->smw_sortkey ), 'SMW::moveSMWPageID' );
			$targetid = $db->insertId();
		} else { // change to given id
			$db->insert( 'smw_ids', array( 'smw_id' => $targetid,
			                              'smw_title' => $row->smw_title,
			                              'smw_namespace' => $row->smw_namespace,
			                              'smw_iw' => $row->smw_iw,
			                              'smw_sortkey' => $row->smw_sortkey ), 'SMW::moveSMWPageID' );
		}
		$db->delete( 'smw_ids', array( 'smw_id' => $curid ), 'SMWSQLStoreLight::moveSMWPageID' );
		$this->changeSMWPageID( $curid, $targetid, $row->smw_namespace, $row->smw_namespace );
	}

	/**
	 * Change an SMW page id across all relevant tables. The id in smw_ids as
	 * such is not touched, but bnodes refering to the old object will be moved
	 * along. The redirect table is also updated (without much effect if the
	 * change happended due to some redirect, since the table should not
	 * contain the id of the redirected page). If namespaces are given, then
	 * they are used to delete any entries that are limited to one particular
	 * namespace (e.g. only properties can be used as properties) instead of
	 * moving them.
	 *
	 * @param $oldid numeric ID that is to be changed
	 * @param $newid numeric ID to which the records are to be changed
	 * @param $oldnamespace namespace of old id's page (-1 to ignore it)
	 * @param $newnamespace namespace of new id's page (-1 to ignore it)
	 * @param $sdata boolean stating whether to update subject references
	 * @param $podata boolean stating if to update property/object references
	 */
	protected function changeSMWPageID( $oldid, $newid, $oldnamespace = - 1, $newnamespace = - 1, $sdata = true, $podata = true ) {
		$fname = 'SMW::changeSMWPageID';
		$db = wfGetDB( DB_MASTER );
		// Update bnode references that use namespace field to store ids:
		if ( $sdata ) { // bnodes are part of the data of a subject
			$db->update( 'smw_ids', array( 'smw_namespace' => $newid ),
			            array( 'smw_title' => '', 'smw_namespace' => $oldid, 'smw_iw' => SMW_SQL2_SMWIW ), $fname );
		}
		// change all id entries in property tables:
		foreach ( SMWSQLStoreLight::getPropertyTables() as $proptable ) {
			if ( $sdata && $proptable->idsubject ) {
				$db->update( $proptable->name, array( 's_id' => $newid ), array( 's_id' => $oldid ), $fname );
			}
			if ( $podata ) {
				if ( ( ( $oldnamespace == - 1 ) || ( $oldnamespace == SMW_NS_PROPERTY ) ) && ( $proptable->fixedproperty == false ) ) {
					if ( ( $newnamespace == - 1 ) || ( $newnamespace == SMW_NS_PROPERTY ) ) {
						$db->update( $proptable->name, array( 'p_id' => $newid ), array( 'p_id' => $oldid ), $fname );
					} else {
						$db->delete( $proptable->name, array( 'p_id' => $oldid ), $fname );
					}
				}
				foreach ( $proptable->objectfields as $fieldname => $type ) {
					if ( $type == 'p' ) {
						$db->update( $proptable->name, array( $fieldname => $newid ), array( $fieldname => $oldid ), $fname );
					}
				}
			}
		}
		// change id entries in concept-related tables:
		if ( $sdata && ( ( $oldnamespace == - 1 ) || ( $oldnamespace == SMW_NS_CONCEPT ) ) ) {
			if ( ( $newnamespace == - 1 ) || ( $newnamespace == SMW_NS_CONCEPT ) ) {
				$db->update( 'smw_conc2', array( 's_id' => $newid ), array( 's_id' => $oldid ), $fname );
				$db->update( 'smw_conccache', array( 's_id' => $newid ), array( 's_id' => $oldid ), $fname );
			} else {
				$db->delete( 'smw_conc2', array( 's_id' => $oldid ), $fname );
				$db->delete( 'smw_conccache', array( 's_id' => $oldid ), $fname );
			}
		}
		if ( $podata ) {
			$db->update( 'smw_conccache', array( 'o_id' => $newid ), array( 'o_id' => $oldid ), $fname );
		}
	}

	/**
	 * Delete all semantic data stored for the given subject. Used for update
	 * purposes.
	 */
	protected function deleteSemanticData( SMWWikiPageValue $subject ) {
		$db = wfGetDB( DB_MASTER );
		$fname = 'SMW::deleteSemanticData';
		$id = $this->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), false );
		if ( $id == 0 ) return; // not (directly) used anywhere yet, maybe a redirect but we do not care here
		foreach ( SMWSQLStoreLight::getPropertyTables() as $proptable ) {
			if ( $proptable->idsubject ) {
				$db->delete( $proptable->name, array( 's_id' => $id ), $fname );
			} elseif ( $proptable->name != 'smw_redi' ) { /// NOTE: redirects are handled by updateRedirects(), not here!
				$db->delete( $proptable->name, array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() ), $fname );
			}
		}
		// also find bnodes used by this ID ...
		$res = $db->select( 'smw_ids', 'smw_id', array( 'smw_title' => '', 'smw_namespace' => $id, 'smw_iw' => SMW_SQL2_SMWIW ), $fname );
		// ... and delete them as well
		while ( $row = $db->fetchObject( $res ) ) {
			foreach ( SMWSQLStoreLight::getPropertyTables() as $proptable ) {
				if ( $proptable->idsubject ) {
					$db->delete( $proptable->name, array( 's_id' => $row->smw_id ), $fname );
				}
			}
		}
		$db->freeResult( $res );
		// free all affected bnodes in one call:
		$db->update( 'smw_ids',	array( 'smw_namespace' => 0 ), array( 'smw_title' => '', 'smw_namespace' => $id, 'smw_iw' => SMW_SQL2_SMWIW ), $fname );
		wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
	}

	/**
	 * Helper method to write information about some redirect. Various updates
	 * can be necessary if redirects are resolved as identities SMW. The title
	 * and namespace of the affected page and of its updated redirect target
	 * are given. The target can be empty ('') to delete any redirect. Returns
	 * the canonical ID that is now to be used for the subject.
	 *
	 * This method does not change the ids of the affected pages, and thus it
	 * is not concerned with updates of the data that is currently stored for
	 * the subject. Normally, a subject that is a redirect will not have other
	 * data, but this method does not depend upon this in any way.
	 *
	 * @note Please make sure you fully understand this code before making any
	 * changes here. Keeping the redirect structure consistent is important,
	 * and errors in this code can go unnoticed for quite some time.
	 */
	protected function updateRedirects( $subject_t, $subject_ns, $curtarget_t = '', $curtarget_ns = - 1 ) {
		global $smwgQEqualitySupport, $smwgEnableUpdateJobs;
		$fname = 'SMW::updateRedirects';

		// *** First get id of subject, old redirect target, and current (new) redirect target ***//
		$sid = $this->getSMWPageID( $subject_t, $subject_ns, '', false ); // find real id of subject, if any
		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$new_tid = $curtarget_t ? ( $this->makeSMWPageID( $curtarget_t, $curtarget_ns, '', false ) ):0; // real id of new target, if given
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( array( 'smw_redi2' ), 'o_id', array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), $fname, array( 'LIMIT' => 1 ) );
		$old_tid = ( $row = $db->fetchObject( $res ) ) ? $row->o_id:0; // real id of old target, if any
		$db->freeResult( $res );
		/// NOTE: $old_tid and $new_tid both ignore further redirects, (intentionally) no redirect chains!

		if ( $old_tid == $new_tid ) { // no change, all happy
			return ( $new_tid == 0 ) ? $sid:$new_tid;
		} // note that this means $old_tid!=$new_tid in all cases below

		// *** Make relevant changes in property tables (don't write the new redirect yet) ***//
		$db = wfGetDB( DB_MASTER ); // now we need to write something
		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->changeSMWPageID( $sid, $new_tid, $subject_ns, $curtarget_ns, false, true );
		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted
			$db->delete( 'smw_redi2', array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), $fname );

			if ( $smwgEnableUpdateJobs && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// entries that refer to old target may in fact refer to subject, but we don't know which: schedule affected pages for update
				$jobs = array();
				foreach ( SMWSQLStoreLight::getPropertyTables() as $proptable ) {
					if ( $proptable->name == 'smw_redi2' ) continue; // can safely be skipped
					if ( $proptable->idsubject ) {
						$from = $db->tableName( $proptable->name ) . ' INNER JOIN ' . $db->tableName( 'smw_ids' ) . ' ON s_id=smw_id';
						$select = 'DISTINCT smw_title AS title,smw_namespace AS namespace';
					} else {
						$from = $db->tableName( $proptable->name );
						$select = 'DISTINCT s_title AS title,s_namespace AS namespace';
					}
					if ( ( $subject_ns == SMW_NS_PROPERTY ) && ( $proptable->fixedproperty == false ) ) {
						$res = $db->select( $from, $select, array( 'p_id' => $old_tid ), $fname );
						while ( $row = $db->fetchObject( $res ) ) {
							$jobs[] = new SMWUpdateJob( Title::makeTitle( $row->namespace, $row->title ) );
						}
						$db->freeResult( $res );
					}
					foreach ( $proptable->objectfields as $fieldname => $type ) {
						if ( $type == 'p' ) {
							$res = $db->select( $from, $select, array( $fieldname => $old_tid ), $fname );
							while ( $row = $db->fetchObject( $res ) ) {
								$jobs[] = new SMWUpdateJob( Title::makeTitle( $row->namespace, $row->title ) );
							}
							$db->freeResult( $res );
						}
					}
				}
				/// NOTE: we do not update the concept cache here; this remains an offline task
				Job::batchInsert( $jobs ); ///NOTE: this only happens if $smwgEnableUpdateJobs was true above
			}
		}

		// *** Finally, write the new redirect data ***//
		if ( $new_tid != 0 ) { // record new redirect
			// Redirecting done right:
			// make a new ID with iw SMW_SQL2_SMWREDIIW or change iw field of current ID in this way, write smw_redi2 table, update canonical cache
			// This order must be obeyed unless you really understand what you are doing!
			if ( ( $old_tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // mark subject as redirect (if it was no redirect before)
				if ( $sid == 0 ) { // every redirect page must have an ID
					$sid = $this->makeSMWPageID( $subject_t, $subject_ns, SMW_SQL2_SMWREDIIW, false );
				} else {
					$db->update( 'smw_ids', array( 'smw_iw' => SMW_SQL2_SMWREDIIW ), array( 'smw_id' => $sid ), $fname );
				}
			}
			$db->insert( 'smw_redi2', array( 's_title' => $subject_t, 's_namespace' => $subject_ns, 'o_id' => $new_tid ), $fname );
			$this->m_ids[" $subject_ns $subject_t C"] = $new_tid; // "iw" is empty here
		} else { // delete old redirect
			// This case implies $old_tid != 0 (or we would have new_tid == old_tid above).
			// Therefore $subject had a redirect, and it must also have an ID. This shows that $sid != 0 here.
			$this->m_ids[" $subject_ns $subject_t C"] = $sid; // "iw" is empty here
			if ( $smwgQEqualitySupport != SMW_EQ_NONE ) { // mark subject as non-redirect
				$db->update( 'smw_ids', array( 'smw_iw' => '' ), array( 'smw_id' => $sid ), $fname );
			}
		}
		// *** Flush some caches to be safe, though they are not essential in program runs with redirect updates ***//
		unset( $this->m_semdata[$sid] ); unset( $this->m_semdata[$new_tid] ); unset( $this->m_semdata[$old_tid] );
		unset( $this->m_sdstate[$sid] ); unset( $this->m_sdstate[$new_tid] ); unset( $this->m_sdstate[$old_tid] );
		return ( $new_tid == 0 ) ? $sid:$new_tid;
	}

	/**
	 * Return the array of predefined property table declarations, initialising
	 * it if necessary. The result is an array of SMWSQLStore2Table objects
	 * indexed by table ids. Note that the ids are only for accessing the data
	 * and should not be assumed to agree with the table name.
	 *
	 * Most function in this class are independent of the available property
	 * tables, although the store might not be able to handle proeprty data for
	 * which no suitable table is given. Note that the cached tables of
	 * SMWSQLStoreLight::$property_table_ids refer to IDs that should be available.
	 * The only other table that must always be available is smw_redi2 for
	 * managing redirects.
	 *
	 * Tables declare value columns ("object fields") by specifying their name
	 * and type. Types are given using letters as documented for
	 * SMWDataValue::getSignature(), or the additional letter:
	 * - p for a reference to an SMW ID as stored in the smw_ids table; this
	 *   corresponds to a data entry of ID "tnwt".
	 *
	 * This letter is specific to this store's ID referencing and must not be
	 * used in SMWDataValue::getSignature()!
	 *
	 * @todo Add a hook for registering additional or modifying given tables.
	 */
	public static function getPropertyTables() {
		if ( count( SMWSQLStoreLight::$prop_tables ) > 0 ) return SMWSQLStoreLight::$prop_tables; // don't initialise twice
		SMWSQLStoreLight::$prop_tables['smw_rels2'] = new SMWSQLStore2Table( 'smw_rels2',
		                                          array( 'o_id' => 'p' ),
			                                      array( 'o_id' ) );
		SMWSQLStoreLight::$prop_tables['smw_atts2'] = new SMWSQLStore2Table( 'smw_atts2',
		                                          array( 'value_xsd' => 't', 'value_num' => 'f', 'value_unit' => 'u' ),
			                                      array( 'value_num', 'value_xsd' ) );
		SMWSQLStoreLight::$prop_tables['smw_text2'] = new SMWSQLStore2Table( 'smw_text2',
		                                          array( 'value_blob' => 'l' ) );
		SMWSQLStoreLight::$prop_tables['smw_spec2'] = new SMWSQLStore2Table( 'smw_spec2',
		                                          array( 'value_string' => 't' ),
			                                      array( 's_id,p_id' ) );
		SMWSQLStoreLight::$prop_tables['smw_spec2']->specpropsonly = true;
		SMWSQLStoreLight::$prop_tables['smw_subs2'] = new SMWSQLStore2Table( 'smw_subs2',
		                                          array( 'o_id' => 'p' ),
			                                      array( 'o_id' ),
												  '_SUBC' );
		SMWSQLStoreLight::$prop_tables['smw_subp2'] = new SMWSQLStore2Table( 'smw_subp2',
		                                          array( 'o_id' => 'p' ),
			                                      array( 'o_id' ),
												  '_SUBP' );
		SMWSQLStoreLight::$prop_tables['smw_inst2'] = new SMWSQLStore2Table( 'smw_inst2',
		                                          array( 'o_id' => 'p' ),
			                                      array( 'o_id' ),
												  '_INST' );
		SMWSQLStoreLight::$prop_tables['smw_redi2'] = new SMWSQLStore2Table( 'smw_redi2',
		                                          array( 'o_id' => 'p' ),
			                                      array( 'o_id' ),
					                              '_REDI' );
		SMWSQLStoreLight::$prop_tables['smw_redi2']->idsubject = false;

		return SMWSQLStoreLight::$prop_tables;
	}

}
