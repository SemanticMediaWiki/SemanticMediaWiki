<?php

/**
 * Class Handling all the read methods for SMWSQLStore3
 *
 * @author Markus Kr�tzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @file
 * @ingroup SMWStore
 */

Class SMWSQLStore3Readers {

	/**
	 * The store used by this store reader
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;

	/// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data
	protected static $in_getSemanticData = 0;

	public function __construct( &$parentstore ) {
		$this->store = $parentstore;
	}

	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		wfProfileIn( "SMWSQLStore3::getSemanticData (SMW)" );

		// Do not clear the cache when called recursively.
		self::$in_getSemanticData++;

		// *** Find out if this subject exists ***//
		$sortkey = '';
		$sid = $this->store->smwIds->getSMWPageIDandSort( $subject->getDBkey(), $subject->getNamespace(),
			$subject->getInterwiki(), $subject->getSubobjectName(), $sortkey, true );
		if ( $sid == 0 ) { // no data, save our time
			/// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
			self::$in_getSemanticData--;
			wfProfileOut( "SMWSQLStore3::getSemanticData (SMW)" );
			return new SMWSemanticData( $subject );
		}

		// *** Prepare the cache ***//
		if ( !array_key_exists( $sid, $this->store->m_semdata ) ) { // new cache entry
			$this->store->m_semdata[$sid] = new SMWSql3StubSemanticData( $subject, $this->store, false );
			if ( $subject->getSubobjectName() === '' ) { // no sortkey for subobjects
				$this->store->m_semdata[$sid]->addPropertyStubValue( '_SKEY', array( $sortkey ) );
			}
			$this->store->m_sdstate[$sid] = array();
			// Note: the sortkey is always set but belongs to no property table,
			// hence no entry in $this->store->m_sdstate[$sid] is made.
		}

		if ( ( count( $this->store->m_semdata ) > 20 ) && ( self::$in_getSemanticData == 1 ) ) {
			// prevent memory leak;
			// It is not so easy to find the sweet spot between cache size and performance gains (both memory and time),
			// The value of 20 was chosen by profiling runtimes for large inline queries and heavily annotated pages.
			$this->store->m_semdata = array( $sid => $this->store->m_semdata[$sid] );
			$this->store->m_sdstate = array( $sid => $this->store->m_sdstate[$sid] );
		}

		// *** Read the data ***//
		foreach ( SMWSQLStore3::getPropertyTables() as $tid => $proptable ) {
			if ( array_key_exists( $tid, $this->store->m_sdstate[$sid] ) ) continue;

			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeid ) {
					$relevant = $relevant || SMWSQLStore3::tableFitsType( $tid, $typeid );
				}
				if ( !$relevant ) continue;
			}

			$data = $this->fetchSemanticData( $sid, $subject, $proptable );

			foreach ( $data as $d ) {
				$this->store->m_semdata[$sid]->addPropertyStubValue( reset( $d ), end( $d ) );
			}

			$this->store->m_sdstate[$sid][$tid] = true;
		}

		self::$in_getSemanticData--;

		wfProfileOut( "SMWSQLStore3::getSemanticData (SMW)" );

		return $this->store->m_semdata[$sid];
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
		wfProfileIn( "SMWSQLStore3::getPropertyValues (SMW)" );

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMWDIProperty( $property->getKey(), false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestoptions );
		} elseif ( !is_null( $subject ) ) { // subject given, use semantic data cache
			$sd = $this->getSemanticData( $subject, array( $property->findPropertyTypeID() ) );
			$result = $this->store->applyRequestOptions( $sd->getPropertyValues( $property ), $requestoptions );
		} else { // no subject given, get all values for the given property
			$pid = $this->store->smwIds->getSMWPropertyID( $property );
			$tableid = SMWSQLStore3::findPropertyTableID( $property );

			if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
				wfProfileOut( "SMWSQLStore3::getPropertyValues (SMW)" );
				return array();
			}

			$proptables = SMWSQLStore3::getPropertyTables();
			$data = $this->fetchSemanticData( $pid, $property, $proptables[$tableid], false, $requestoptions );
			$result = array();
			$propertyTypeId = $property->findPropertyTypeID();
			$propertyDiId = SMWDataValueFactory::getDataItemId( $propertyTypeId );

			foreach ( $data as $dbkeys ) {
				try {
					$diHandler = $this->store->getDataItemHandlerForDIType( $propertyDiId );
					$result[] = $diHandler->dataItemFromDBKeys( $dbkeys );
				} catch ( SMWDataItemException $e ) {
					// maybe type assignment changed since data was stored;
					// don't worry, but we can only drop the data here
				}
			}
		}

		wfProfileOut( "SMWSQLStore3::getPropertyValues (SMW)" );

		return $result;
	}

	/**
	 * Helper function for reading all data for from a given property table (specified by an
	 * SMWSQLStore3Table object), based on certain restrictions. The function can filter data
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
	 * @todo This uses some hacky code; see FIXME in method.
	 *
	 * @param integer $id
	 * @param SMWDataItem $object
	 * @param SMWSQLStore3Table $proptable
	 * @param boolean $issubject
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array
	 */
	protected function fetchSemanticData( $id, $object, $proptable, $issubject = true, $requestoptions = null ) {
		// stop if there is not enough data:
		// properties always need to be given as object, subjects at least if !$proptable->idsubject
		if ( ( $id == 0 ) || ( is_null( $object ) && ( !$issubject || !$proptable->idsubject ) ) ) return array();

		wfProfileIn( "SMWSQLStore3::fetchSemanticData-" . $proptable->name .  " (SMW)" );
		$result = array();
		$db = wfGetDB( DB_SLAVE );

		// ***  First build $from, $select, and $where for the DB query  ***//
		$from   = $db->tableName( $proptable->name ); // always use actual table
		$select = '';
		$where  = '';

		if ( $issubject ) { // restrict subject, select property
			$where .= ( $proptable->idsubject ) ? 's_id=' . $db->addQuotes( $id ) :
					  's_title=' . $db->addQuotes( $object->getDBkey() ) .
					  ' AND s_namespace=' . $db->addQuotes( $object->getNamespace() );
			if ( !$proptable->fixedproperty ) { // get property name
				$from .= ' INNER JOIN ' . $db->tableName( 'smw_ids' ) . ' AS p ON p_id=p.smw_id';
				$select .= 'p.smw_title as prop';
			} // else: fixed property, no select needed at all to get at it
		} elseif ( !$proptable->fixedproperty ) { // restrict property, but don't select subject
			$where .= 'p_id=' . $db->addQuotes( $id );
		}

		$valuecount = 0;
		$usedistinct = true; // use DISTINCT option only if no text blobs are among values
		$selectvalues = array(); // array for all values to be selected, kept to help finding value and label fields below

		$fields = $proptable->getFields( $this->store );
		foreach ( $fields as $fieldname => $typeid ) { // now add select entries for object column(s)
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
			list( $valueField, $labelField ) = $this->store->getTypeSignature( $object->findPropertyTypeID() );

			/// FIXME This is a hack.
			//Hacky, we assume that valueFields that are in smw_ids table start with 'smw'
			//the alias for smw_ids table is o0 (assigned above)
			if( substr( $valueField, 0, 3 ) == 'smw' ) {
				$valueField = "o0.".$valueField;
			}

			if( substr( $labelField, 0, 3 ) == 'smw' ) {
				$labelField = "o0.".$labelField;
			}

			$where .= $this->store->getSQLConditions( $requestoptions, $valueField, $labelField, $where !== '' );
		} else {
			$valueField = $labelField = '';
		}

		// ***  Now execute the query and read the results  ***//
		$res = $db->select( $from, $select, $where, 'SMW::getSemanticData',
		       ( $usedistinct ? $this->store->getSQLOptions( $requestoptions, $valueField ) + array( 'DISTINCT' ) :
		                        $this->store->getSQLOptions( $requestoptions, $valueField ) ) );

		foreach ( $res as $row ) {
			if ( $issubject ) { // use joined or predefined property name
				$propertyname = $proptable->fixedproperty ? $proptable->fixedproperty : $row->prop;
			}

			$valuekeys = array();
			for ( $i = 0; $i < $valuecount; $i += 1 ) { // read the value fields from the current row
				$fieldname = "v$i";
				$valuekeys[] = $row->$fieldname;
			}

			// Filter out any accidentally retrieved internal things (interwiki starts with ":"):
			if ( implode( '', $fields ) != 'p' || count( $valuekeys ) < 3 ||
			     $valuekeys[2] === '' ||  $valuekeys[2]{0} != ':' ) {
				$result[] = $issubject ? array( $propertyname, $valuekeys ) : $valuekeys;
			}
		}

		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStore3::fetchSemanticData-" . $proptable->name .  " (SMW)" );

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
		wfProfileIn( "SMWSQLStore3::getPropertySubjects (SMW)" );

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMWDIProperty( $property->getKey(), false );
			$result = $this->getPropertyValues( $value, $noninverse, $requestoptions );
			wfProfileOut( "SMWSQLStore3::getPropertySubjects (SMW)" );
			return $result;
		}

		// First build $select, $from, and $where for the DB query
		$where = $from = '';
		$pid = $this->store->smwIds->getSMWPropertyID( $property );
		$tableid = SMWSQLStore3::findPropertyTableID( $property );

		if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
			wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
			return array();
		}

		$proptables = SMWSQLStore3::getPropertyTables();
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
		                    $where . $this->store->getSQLConditions( $requestoptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
		                    'SMW::getPropertySubjects',
		                    $this->store->getSQLOptions( $requestoptions, 'smw_sortkey' ) );

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
		wfProfileOut( "SMWSQLStore3::getPropertySubjects (SMW)" );

		return $result;
	}


	/**
	 * Helper function to compute from and where strings for a DB query so that
	 * only rows of the given value object match. The parameter $tableindex
	 * counts that tables used in the query to avoid duplicate table names. The
	 * parameter $proptable provides the SMWSQLStore3Table object that is
	 * queried.
	 *
	 * @todo Maybe do something about redirects. The old code was
	 * $oid = $this->store->smwIds->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki(),false);
	 *
	 * @param string $from
	 * @param string $where
	 * @param SMWSQLStore3Table $proptable
	 * @param SMWDataItem $value
	 * @param integer $tableindex
	 */
	protected function prepareValueQuery( &$from, &$where, $proptable, $value, $tableindex = 1 ) {
		$db = wfGetDB( DB_SLAVE );

		if ( $value instanceof SMWDIContainer ) { // recursive handling of containers
			$keys = array_keys( $proptable->getFields( $this->store ) );
			$joinfield = "t$tableindex." . reset( $keys ); // this must be a type 'p' object
			$proptables = SMWSQLStore3::getPropertyTables();
			$semanticData = $value->getSemanticData();

			foreach ( $semanticData->getProperties() as $subproperty ) {
				$tableid = SMWSQLStore3::findPropertyTableID( $subproperty );
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
						$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.p_id=" . $db->addQuotes( $this->store->smwIds->getSMWPropertyID( $subproperty ) );
					}

					$this->prepareValueQuery( $from, $where, $subproptable, $subvalue, $tableindex );
				}
			}
		} elseif ( !is_null( $value ) ) { // add conditions for given value
			///since SMW.storerewrite we get the array of where conds (fieldname=>value) from the DIHander class
			//This causes a database error when called for special properties as they have different table structure
			//unknown to the DIHandlers. Do we really need different table structure for special properties?
			$diHandler = $this->store->getDataItemHandlerForDIType( $value->getDIType() );
			foreach ( $diHandler->getWhereConds( $value ) as $fieldname => $value ) {
				$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.$fieldname=" . $db->addQuotes( $value );
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
		wfProfileIn( "SMWSQLStore3::getAllPropertySubjects (SMW)" );
		$result = $this->getPropertySubjects( $property, null, $requestoptions );
		wfProfileOut( "SMWSQLStore3::getAllPropertySubjects (SMW)" );

		return $result;
	}

	/**
	 * @see SMWStore::getProperties
	 *
	 * @param SMWDIWikiPage $subject
	 * @param SMWRequestOptions $requestoptions
	 */
	public function getProperties( SMWDIWikiPage $subject, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore3::getProperties (SMW)" );
		$sid = $this->store->smwIds->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName() );

		if ( $sid == 0 ) { // no id, no page, no properties
			wfProfileOut( "SMWSQLStore3::getProperties (SMW)" );
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

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
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
					$where . $this->store->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey' ),
					'SMW::getProperties', $this->store->getSQLOptions( $suboptions, 'smw_sortkey' ) );

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

		$result = $this->store->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStore3::getProperties (SMW)" );

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
		wfProfileIn( "SMWSQLStore3::getInProperties (SMW)" );

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

		$tableIds = SMWSQLStore3::findAllDiTypeTableIds( $value->getDIType() );
		$proptables = SMWSQLStore3::getPropertyTables();
		foreach ( $tableIds as $tid ) {
			$proptable = $proptables[$tid];
			$where = $from = '';
			if ( $proptable->fixedproperty == false ) { // join smw_ids to get property titles
				$from = $db->tableName( 'smw_ids' ) . " INNER JOIN " . $db->tableName( $proptable->name ) . " AS t1 ON t1.p_id=smw_id";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey',
						// select sortkey since it might be used in ordering (needed by Postgres)
						$where . $this->store->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
						'SMW::getInProperties', $this->store->getSQLOptions( $suboptions, 'smw_sortkey' ) );

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

		$result = $this->store->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStore3::getInProperties (SMW)" );

		return $result;
	}

	/**
	 * This method adds property-values of a subject from a property-value table into the given 
	 * SemanticData object.
	 * @todo Use share code with getSemanticData above, maybe remove it completely with this??
	 *
	 * @param SMWSql3StubSemanticData $semData
	 * @param SMWSQLStore3Table $proptable
	 *
	 * @since 1.8
	 */
	public function addTableSemanticData( $sid, SMWSql3StubSemanticData &$semData, SMWSQLStore3Table $proptable ) {
		$subject = $semData->getSubject();
		$data = $this->fetchSemanticData( $sid, $subject, $proptable );

		foreach ( $data as $d ) {
			$semData->addPropertyStubValue( reset( $d ), end( $d ) );
		}
	}

}
