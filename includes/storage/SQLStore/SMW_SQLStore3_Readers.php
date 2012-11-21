<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Class to provide all basic read methods for SMWSQLStore3.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3Readers {

	/**
	 * The store used by this store reader
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;

	/// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data
	protected static $in_getSemanticData = 0;

	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
	}

	/**
	 * @see SMWStore::getSemanticData()
	 * @since 1.8
	 */
	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		wfProfileIn( "SMWSQLStore3::getSemanticData (SMW)" );

		// *** Find out if this subject exists ***//
		$sortkey = '';
		$sid = $this->store->smwIds->getSMWPageIDandSort( $subject->getDBkey(),
							$subject->getNamespace(),
							$subject->getInterwiki(),
							$subject->getSubobjectName(),
							$sortkey, true, true );

		if ( $sid == 0 ) {
			// We consider redirects for getting $sid,
			// so $sid == 0 also means "no redirects".
			wfProfileOut( "SMWSQLStore3::getSemanticData (SMW)" );
			return new SMWSemanticData( $subject );
		}

		$propertyTableHashes = $this->store->smwIds->getPropertyTableHashes( $sid );

		foreach ( SMWSQLStore3::getPropertyTables() as $tid => $proptable ) {
			if ( !array_key_exists( $proptable->getName(), $propertyTableHashes ) ) {
				continue;
			}

			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeId ) {
					$diType = SMWDataValueFactory::getDataItemId( $typeId );
					$relevant = $relevant || ( $proptable->getDiType() == $diType );
					if ( $relevant ) break;
				}
				if ( !$relevant ) continue;
			}

			$this->getSemanticDataFromTable( $sid, $subject, $proptable );
		}

		// Note: the sortkey is always set but belongs to no property table,
		// hence no entry in $this->store->m_sdstate[$sid] is made.
		self::$in_getSemanticData++;
		$this->initSemanticDataCache( $sid, $subject );
		$this->store->m_semdata[$sid]->addPropertyStubValue( '_SKEY', array( '', $sortkey ) );
		self::$in_getSemanticData--;

		wfProfileOut( "SMWSQLStore3::getSemanticData (SMW)" );

		return $this->store->m_semdata[$sid];
	}

	/**
	 * Helper method to make sure there is a cache entry for the data about
	 * the given subject with the given ID.
	 *
	 * @todo The management of this cache should be revisited.
	 *
	 * @since 1.8
	 */
	protected function initSemanticDataCache( $sid, SMWDIWikiPage $subject ) {
		// *** Prepare the cache ***//
		if ( !array_key_exists( $sid, $this->store->m_semdata ) ) { // new cache entry
			$this->store->m_semdata[$sid] = new SMWSql3StubSemanticData( $subject, $this->store, false );
			$this->store->m_sdstate[$sid] = array();
		}

		if ( ( count( $this->store->m_semdata ) > 20 ) && ( self::$in_getSemanticData == 1 ) ) {
			// prevent memory leak;
			// It is not so easy to find the sweet spot between cache size and performance gains (both memory and time),
			// The value of 20 was chosen by profiling runtimes for large inline queries and heavily annotated pages.
			// However, things might have changed in the meantime ...
			$this->store->m_semdata = array( $sid => $this->store->m_semdata[$sid] );
			$this->store->m_sdstate = array( $sid => $this->store->m_sdstate[$sid] );
		}
	}

	/**
	 * Fetch the data storder about one subject in one particular table.
	 *
	 * @param $sid integer
	 * @param $subject SMWDIWikiPage
	 * @param $proptable SMWSQLStore3Table
	 * @return SMWSemanticData
	 * @since 1.8
	 */
	protected function getSemanticDataFromTable( $sid, SMWDIWikiPage $subject, SMWSQLStore3Table $proptable ) {
		// Do not clear the cache when called recursively.
		self::$in_getSemanticData++;

		$this->initSemanticDataCache( $sid, $subject );

		if ( array_key_exists( $proptable->getName(), $this->store->m_sdstate[$sid] ) ) {
			self::$in_getSemanticData--;
			return $this->store->m_semdata[$sid];
		}

		// *** Read the data ***//
		$data = $this->fetchSemanticData( $sid, $subject, $proptable );
		foreach ( $data as $d ) {
			$this->store->m_semdata[$sid]->addPropertyStubValue( reset( $d ), end( $d ) );
		}
		$this->store->m_sdstate[$sid][$proptable->getName()] = true;

		self::$in_getSemanticData--;
		return $this->store->m_semdata[$sid];
	}

	/**
	 * @see SMWStore::getPropertyValues
	 *
	 * @todo Retrieving all sortkeys (values for _SKEY with $subject null)
	 * is not supported. An empty array will be given.
	 *
	 * @since 1.8
	 * @param $subject mixed SMWDIWikiPage or null
	 * @param $property SMWDIProperty
	 * @param $requestoptions SMWRequestOptions
	 * @return array of SMWDataItem
	 */
	public function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore3::getPropertyValues (SMW)" );

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMWDIProperty( $property->getKey(), false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestoptions );
		} elseif ( !is_null( $subject ) ) { // subject given, use semantic data cache
			$sid = $this->store->smwIds->getSMWPageID( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				$subject->getSubobjectName(), true );
			if ( $sid == 0 ) {
				$result = array();
			} elseif ( $property->getKey() == '_SKEY' ) {
				$this->store->smwIds->getSMWPageIDandSort( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				$subject->getSubobjectName(), $sortKey, true );
				$sortKeyDi = new SMWDIBlob( $sortKey );
				$result = $this->store->applyRequestOptions( array( $sortKeyDi ), $requestoptions );
			} else {
				$propTableId = $this->store->findPropertyTableID( $property );
				$proptables = SMWSQLStore3::getPropertyTables();
				$sd = $this->getSemanticDataFromTable( $sid, $subject, $proptables[$propTableId] );
				$result = $this->store->applyRequestOptions( $sd->getPropertyValues( $property ), $requestoptions );
			}
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
	 * Helper function for reading all data for from a given property table
	 * (specified by an SMWSQLStore3Table object), based on certain
	 * restrictions. The function can filter data based on the subject (1)
	 * or on the property it belongs to (2) -- but one of those must be
	 * done. The Boolean $issubject is true for (1) and false for (2).
	 *
	 * In case (1), the first two parameters are taken to refer to a
	 * subject; in case (2) they are taken to refer to a property. In any
	 * case, the retrieval is limited to the specified $proptable. The
	 * parameters are an internal $id (of a subject or property), and an
	 * $object (being an SMWDIWikiPage or SMWDIProperty). Moreover, when
	 * filtering by property, it is assumed that the given $proptable
	 * belongs to the property: if it is a table with fixed property, it
	 * will not be checked that this is the same property as the one that
	 * was given in $object.
	 *
	 * In case (1), the result in general is an array of pairs (arrays of
	 * size 2) consisting of a property key (string), and DB keys (array if
	 * many, string if one) from which a datvalue object for this value can
	 * be built. It is possible that some of the DB keys are based on
	 * internal objects; these will be represented by similar result arrays
	 * of (recursive calls of) fetchSemanticData().
	 *
	 * In case (2), the result is simply an array of DB keys (array)
	 * without the property keys. Container objects will be encoded with
	 * nested arrays like in case (1).
	 *
	 * @todo Maybe share DB handler; asking for it seems to take quite some
	 * time and we do not want to change it in one call.
	 *
	 * @since 1.8
	 * @param integer $id
	 * @param SMWDataItem $object
	 * @param SMWSQLStore3Table $proptable
	 * @param boolean $issubject
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array
	 */
	protected function fetchSemanticData( $id, SMWDataItem $object = null, SMWSQLStore3Table $proptable, $issubject = true, SMWRequestOptions $requestoptions = null ) {
		// stop if there is not enough data:
		// properties always need to be given as object,
		// subjects at least if !$proptable->idsubject
		if ( ( $id == 0 ) ||
			( is_null( $object ) && ( !$issubject || !$proptable->usesIdSubject() ) ) )
			return array();

		wfProfileIn( "SMWSQLStore3::fetchSemanticData-" . $proptable->getName() .  " (SMW)" );
		$result = array();
		$db = wfGetDB( DB_SLAVE );

		$diHandler = $this->store->getDataItemHandlerForDIType( $proptable->getDiType() );

		// ***  First build $from, $select, and $where for the DB query  ***//
		$from   = $db->tableName( $proptable->getName() ); // always use actual table
		$select = '';
		$where  = '';

		if ( $issubject ) { // restrict subject, select property
			$where .= ( $proptable->usesIdSubject() ) ? 's_id=' . $db->addQuotes( $id ) :
					  's_title=' . $db->addQuotes( $object->getDBkey() ) .
					  ' AND s_namespace=' . $db->addQuotes( $object->getNamespace() );
			if ( !$proptable->isFixedPropertyTable() ) { // select property name
				$from .= ' INNER JOIN ' . $db->tableName( SMWSql3SmwIds::tableName ) . ' AS p ON p_id=p.smw_id';
				$select .= 'p.smw_title as prop';
			} // else: fixed property, no select needed
		} elseif ( !$proptable->isFixedPropertyTable() ) { // restrict property only
			$where .= 'p_id=' . $db->addQuotes( $id );
		}

		$valuecount = 0;
		// Don't use DISTINCT for value of one subject:
		$usedistinct = !$issubject;

		$valueField = $diHandler->getIndexField();
		$labelField = $diHandler->getLabelField();
		$fields = $diHandler->getFetchFields();
		foreach ( $fields as $fieldname => $typeid ) { // select object column(s)
			if ( $typeid == 'p' ) { // get data from ID table
				$from .= ' INNER JOIN ' . $db->tableName( SMWSql3SmwIds::tableName ) . " AS o$valuecount ON $fieldname=o$valuecount.smw_id";
				$select .= ( ( $select !== '' ) ? ',' : '' ) .
					"$fieldname AS id$valuecount" .
					",o$valuecount.smw_title AS v$valuecount" .
					",o$valuecount.smw_namespace AS v" . ( $valuecount + 1 ) .
					",o$valuecount.smw_iw AS v" . ( $valuecount + 2 ) .
					",o$valuecount.smw_sortkey AS v" . ( $valuecount + 3 ) .
					",o$valuecount.smw_subobject AS v" . ( $valuecount + 4 );

				if ( $valueField == $fieldname ) {
					$valueField = "o$valuecount.smw_sortkey";
				}
				if ( $labelField == $fieldname ) {
					$labelField = "o$valuecount.smw_sortkey";
				}

				$valuecount += 4;
			} else {
				$select .= ( ( $select !== '' ) ? ',' : '' ) .
					"$fieldname AS v$valuecount";
			}

			// Don't use DISTINCT with text blobs:
			if ( $typeid == 'l' ) $usedistinct = false;
			$valuecount += 1;
		}

		if ( !$issubject ) { // Apply sorting/string matching; only with given property
			$where .= $this->store->getSQLConditions( $requestoptions, $valueField, $labelField, $where !== '' );
		} else {
			$valueField = '';
		}

		// ***  Now execute the query and read the results  ***//
		$res = $db->select( $from, $select, $where, 'SMW::getSemanticData',
				( $usedistinct ?
					$this->store->getSQLOptions( $requestoptions, $valueField ) + array( 'DISTINCT' ) :
					$this->store->getSQLOptions( $requestoptions, $valueField )
				) );

		foreach ( $res as $row ) {
			if ( $issubject ) { // use joined or predefined property name
				$propertykey = $proptable->isFixedPropertyTable() ? $proptable->getFixedProperty() : $row->prop;
			}

			// Use enclosing array only for results with many values:
			if ( $valuecount > 1 ) {
				$valuekeys = array();
				for ( $i = 0; $i < $valuecount; $i += 1 ) { // read the value fields from the current row
					$fieldname = "v$i";
					$valuekeys[] = $row->$fieldname;
				}
			} else {
				$valuekeys = $row->v0;
			}

			// Filter out any accidentally retrieved internal things (interwiki starts with ":"):
			if ( $valuecount < 3 || implode( '', $fields ) != 'p' ||
			     $valuekeys[2] === '' ||  $valuekeys[2]{0} != ':' ) {
				$result[] = $issubject ? array( $propertykey, $valuekeys ) : $valuekeys;
			}
		}

		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStore3::fetchSemanticData-" . $proptable->getName() .  " (SMW)" );

		return $result;
	}

	/**
	 * @see SMWStore::getPropertySubjects
	 *
	 * @todo This method cannot retrieve subjects for sortkeys, i.e., for
	 * property _SKEY. Only empty arrays will be returned there.
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

		if ( $proptable->usesIdSubject() ) { // join with ID table to get title data
			$from = $db->tableName( SMWSql3SmwIds::tableName ) . " INNER JOIN " . $db->tableName( $proptable->getName() ) . " AS t1 ON t1.s_id=smw_id";
			$select = 'smw_title, smw_namespace, smw_iw, smw_sortkey, smw_subobject';
		} else { // no join needed, title+namespace as given in proptable
			$from = $db->tableName( $proptable->getName() ) . " AS t1";
			$select = 's_title AS smw_title, s_namespace AS smw_namespace, \'\' AS smw_iw, s_title AS smw_sortkey, \'\' AS smw_subobject';
		}

		if ( !$proptable->isFixedPropertyTable() ) {
			$where .= ( $where ? ' AND ' : '' ) . "t1.p_id=" . $db->addQuotes( $pid );
		}

		$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

		// ***  Now execute the query and read the results  ***//
		$result = array();
		$res = $db->select( $from, 'DISTINCT ' . $select,
		                    $where . $this->store->getSQLConditions( $requestoptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
		                    'SMW::getPropertySubjects',
		                    $this->store->getSQLOptions( $requestoptions, 'smw_sortkey' ) );

		$diHandler = $this->store->getDataItemHandlerForDIType( SMWDataItem::TYPE_WIKIPAGE );

		foreach ( $res as $row ) {
			try {
				if ( $row->smw_iw === '' || $row->smw_iw{0} != ':' ) { // filter special objects
					$result[] = $diHandler->dataItemFromDBKeys( array_values( (array)$row ) );
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
	 * @note This method cannot handle DIContainer objects with sortkey
	 * properties correctly. This should never occur, but it would be good
	 * to fail in a more controlled way if it ever does.
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

					if ( $subproptable->usesIdSubject() ) { // simply add property table to check values
						$from .= " INNER JOIN " . $db->tableName( $subproptable->getName() ) . " AS t$tableindex ON t$tableindex.s_id=$joinfield";
					} else { // exotic case with table that uses subject title+namespace in container object (should never happen in SMW core)
						$from .= " INNER JOIN " . $db->tableName( SMWSql3SmwIds::tableName ) . " AS ids$tableindex ON ids$tableindex.smw_id=$joinfield" .
						         " INNER JOIN " . $db->tableName( $subproptable->getName() ) . " AS t$tableindex ON " .
						         "t$tableindex.s_title=ids$tableindex.smw_title AND t$tableindex.s_namespace=ids$tableindex.smw_namespace";
					}

					if ( !$subproptable->isFixedPropertyTable() ) { // the ID we get should be !=0, so no point in filtering the converse
						$where .= ( $where ? ' AND ' : '' ) . "t$tableindex.p_id=" . $db->addQuotes( $this->store->smwIds->getSMWPropertyID( $subproperty ) );
					}

					$this->prepareValueQuery( $from, $where, $subproptable, $subvalue, $tableindex );
				}
			}
		} elseif ( !is_null( $value ) ) { // add conditions for given value
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
			$from = $db->tableName( $proptable->getName() );

			if ( $proptable->usesIdSubject() ) {
				$where = 's_id=' . $db->addQuotes( $sid );
			} elseif ( $subject->getInterwiki() === '' ) {
				$where = 's_title=' . $db->addQuotes( $subject->getDBkey() ) . ' AND s_namespace=' . $db->addQuotes( $subject->getNamespace() );
			} else { // subjects with non-emtpy interwiki cannot have properties
				continue;
			}

			if ( !$proptable->isFixedPropertyTable() ) { // select all properties
				$from .= " INNER JOIN " . $db->tableName( SMWSql3SmwIds::tableName ) . " ON smw_id=p_id";
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
					$result[] = new SMWDIProperty( $proptable->getFixedProperty() );
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
	 * @since 1.8
	 * @see SMWStore::getInProperties
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

		$diType = $value->getDIType();

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			if ( $diType != $proptable->getDiType() ) {
				continue;
			}

			$where = $from = '';
			if ( !$proptable->isFixedPropertyTable() ) { // join ID table to get property titles
				$from = $db->tableName( SMWSql3SmwIds::tableName ) . " INNER JOIN " . $db->tableName( $proptable->getName() ) . " AS t1 ON t1.p_id=smw_id";
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
				$from = $db->tableName( $proptable->getName() ) . " AS t1";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );
				$res = $db->select( $from, '*', $where, 'SMW::getInProperties', array( 'LIMIT' => 1 ) );

				if ( $db->numRows( $res ) > 0 ) {
					$result[] = new SMWDIProperty( $proptable->getFixedProperty() );
				}
			}
			$db->freeResult( $res );
		}

		$result = $this->store->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStore3::getInProperties (SMW)" );

		return $result;
	}

}
