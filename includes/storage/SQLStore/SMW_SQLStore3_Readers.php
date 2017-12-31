<?php

use SMW\DataTypeRegistry;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\TableDefinition;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\TableBuilder\FieldType;

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
	private $store;

	/**
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var TraversalPropertyLookup
	 */
	private $traversalPropertyLookup;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	public function __construct( SMWSQLStore3 $parentStore, $factory ) {
		$this->store = $parentStore;
		$this->factory = $factory;
		$this->traversalPropertyLookup = $this->factory->newTraversalPropertyLookup();
		$this->semanticDataLookup = $this->factory->newSemanticDataLookup();
	}

	/**
	 * @see SMWStore::getSemanticData()
	 * @since 1.8
	 *
	 * @param DIWikiPage $subject
	 * @param string[]|bool $filter
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {

		// *** Find out if this subject exists ***//
		$sortKey = '';

		$sid = $this->store->smwIds->getSMWPageIDandSort(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName(),
			$sortKey,
			true,
			true
		);

		// Ensures that a cached item to contain an expected sortKey when
		// for example the ID was just created and the sortKey from the DB
		// is empty otherwise the DB wins over the invoked sortKey
		if ( !$sortKey ) {
			$sortKey = $subject->getSortKey();
		}

		$subject->setSortKey( $sortKey );

		if ( $sid == 0 ) {
			// We consider redirects for getting $sid,
			// so $sid == 0 also means "no redirects".
			return new SMWSemanticData( $subject );
		}

		$propertyTableHashes = $this->store->smwIds->getPropertyTableHashes( $sid );

		foreach ( $this->store->getPropertyTables() as $tid => $proptable ) {
			if ( !array_key_exists( $proptable->getName(), $propertyTableHashes ) ) {
				continue;
			}

			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeId ) {
					$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeId );
					$relevant = $relevant || ( $proptable->getDiType() == $diType );
					if ( $relevant ) {
						break;
					}
				}
				if ( !$relevant ) {
					continue;
				}
			}

			$this->semanticDataLookup->getSemanticDataFromTable( $sid, $subject, $proptable );
		}

		// Note: the sortkey is always set but belongs to no property table,
		// hence no entry in $this->store->m_sdstate[$sid] is made.
		$this->semanticDataLookup->lockCache();
		$this->semanticDataLookup->initLookupCache( $sid, $subject );

		$semanticData = $this->semanticDataLookup->getSemanticDataById(
			$sid
		);

		// Avoid adding a sortkey for an already extended stub
		if ( !$semanticData->hasProperty( new DIProperty( '_SKEY' ) ) ) {
			$semanticData->addPropertyStubValue( '_SKEY', array( '', $sortKey ) );
		}

		$this->semanticDataLookup->unlockCache();

		return $semanticData;
	}

	/**
	 * @see SMWStore::getPropertyValues
	 *
	 * @todo Retrieving all sortkeys (values for _SKEY with $subject null)
	 * is not supported. An empty array will be given.
	 *
	 * @since 1.8
	 *
	 * @param $subject mixed DIWikiPage or null
	 * @param $property SMWDIProperty
	 * @param $requestOptions SMWRequestOptions
	 *
	 * @return SMWDataItem[]
	 */
	public function getPropertyValues( $subject, SMWDIProperty $property, $requestOptions = null ) {

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMW\DIProperty( $property->getKey(), false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestOptions );
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
				$result = $this->store->applyRequestOptions( array( $sortKeyDi ), $requestOptions );
			} else {
				$propTableId = $this->store->findPropertyTableID( $property );
				$proptables =  $this->store->getPropertyTables();

				if ( !isset( $proptables[$propTableId] ) ) {
					return array();
				}

				$sd = $this->semanticDataLookup->getSemanticDataFromTable( $sid, $subject, $proptables[$propTableId] );
				$result = $this->store->applyRequestOptions( $sd->getPropertyValues( $property ), $requestOptions );
			}
		} else { // no subject given, get all values for the given property
			$pid = $this->store->smwIds->getSMWPropertyID( $property );
			$tableid =  $this->store->findPropertyTableID( $property );

			if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
				return array();
			}

			$proptables =  $this->store->getPropertyTables();
			$data = $this->semanticDataLookup->fetchSemanticData(
				$pid,
				$property,
				$proptables[$tableid],
				false,
				$requestOptions
			);

			$result = array();
			$propertyTypeId = $property->findPropertyTypeID();
			$propertyDiId = DataTypeRegistry::getInstance()->getDataItemId( $propertyTypeId );

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


		return $result;
	}

	/**
	 * @see SMWStore::getPropertySubjects
	 *
	 * @todo This method cannot retrieve subjects for sortkeys, i.e., for
	 * property _SKEY. Only empty arrays will be returned there.
	 *
	 * @param SMWDIProperty $property
	 * @param SMWDataItem|null $value
	 * @param SMWRequestOptions|null $requestOptions
	 *
	 * @return array of DIWikiPage
	 */
	public function getPropertySubjects( SMWDIProperty $property, SMWDataItem $value = null, SMWRequestOptions $requestOptions = null ) {

		/// TODO: should we share code with #ask query computation here? Just use queries?

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new SMW\DIProperty( $property->getKey(), false );
			$result = $this->getPropertyValues( $value, $noninverse, $requestOptions );
			return $result;
		}

		// #1222, Filter those where types don't match (e.g property = _txt
		// and value = _wpg)
		if ( $value !== null && DataTypeRegistry::getInstance()->getDataItemId( $property->findPropertyTypeID() ) !== $value->getDIType() ) {
			return array();
		}

		// First build $select, $from, and $where for the DB query
		$where = $from = '';
		$pid = $this->store->smwIds->getSMWPropertyID( $property );
		$tableid =  $this->store->findPropertyTableID( $property );

		if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
			return array();
		}

		$proptables =  $this->store->getPropertyTables();
		$proptable = $proptables[$tableid];
		$db = $this->store->getConnection();

		if ( $proptable->usesIdSubject() ) { // join with ID table to get title data
			$from = $db->tableName( SMWSql3SmwIds::TABLE_NAME ) . " INNER JOIN " . $db->tableName( $proptable->getName() ) . " AS t1 ON t1.s_id=smw_id";
			$select = 'smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort';
		} else { // no join needed, title+namespace as given in proptable
			$from = $db->tableName( $proptable->getName() ) . " AS t1";
			$select = 's_title AS smw_title, s_namespace AS smw_namespace, \'\' AS smw_iw, \'\' AS smw_subobject, s_title AS smw_sortkey, s_title AS smw_sort';
		}

		if ( !$proptable->isFixedPropertyTable() ) {
			$where .= ( $where ? ' AND ' : '' ) . "t1.p_id=" . $db->addQuotes( $pid );
		}

		$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

		// ***  Now execute the query and read the results  ***//
		$result = array();

		if ( $proptable->usesIdSubject() ) {
			$where .= ( $where !== '' ? ' AND ' : ' ' ) . "smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
			" AND smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWDELETEIW ) .
			" AND smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWREDIIW );
		}

		$res = $db->select(
			$from,
			'DISTINCT ' . $select,
			$where . $this->store->getSQLConditions( $requestOptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
			__METHOD__,
			$this->store->getSQLOptions( $requestOptions, 'smw_sort' )
		);

		$diHandler = $this->store->getDataItemHandlerForDIType( SMWDataItem::TYPE_WIKIPAGE );

		$callback = function( $row ) use( $diHandler ) {
			try {
				if ( $row->smw_iw === '' || $row->smw_iw{0} != ':' ) { // filter special objects
					$dbkeys = array(
						$row->smw_title,
						$row->smw_namespace,
						$row->smw_iw,
						$row->smw_sort,
						$row->smw_subobject

					);
					return $diHandler->dataItemFromDBKeys( $dbkeys );
				}
			} catch ( DataItemHandlerException $e ) {
				// silently drop data, should be extremely rare and will usually fix itself at next edit
			}

			$title = ( $row->smw_title !== '' ? $row->smw_title : 'Empty' ) . '/' . $row->smw_namespace;

			// Avoid null return in Iterator
			return $diHandler->dataItemFromDBKeys( [ 'Blankpage/' . $title, NS_SPECIAL, '', '', '' ] );
		};

		$iteratorFactory = ApplicationFactory::getInstance()->getIteratorFactory();

		// Return an iterator and avoid resolving the resources directly as an array
		// as it may contain a large list of possible matches
		$result = $iteratorFactory->newMappingIterator(
			$iteratorFactory->newResultIterator( $res ),
			$callback
		);

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
	 * @param TableDefinition $propTable
	 * @param SMWDataItem $value
	 * @param integer $tableIndex
	 */
	private function prepareValueQuery( &$from, &$where, TableDefinition $propTable, $value, $tableIndex = 1 ) {
		$db = $this->store->getConnection();

		if ( $value instanceof SMWDIContainer ) { // recursive handling of containers
			$keys = array_keys( $propTable->getFields( $this->store ) );
			$joinfield = "t$tableIndex." . reset( $keys ); // this must be a type 'p' object
			$proptables =  $this->store->getPropertyTables();
			$semanticData = $value->getSemanticData();

			foreach ( $semanticData->getProperties() as $subproperty ) {
				$tableid =  $this->store->findPropertyTableID( $subproperty );
				$subproptable = $proptables[$tableid];

				foreach ( $semanticData->getPropertyValues( $subproperty ) as $subvalue ) {
					$tableIndex++;

					if ( $subproptable->usesIdSubject() ) { // simply add property table to check values
						$from .= " INNER JOIN " . $db->tableName( $subproptable->getName() ) . " AS t$tableIndex ON t$tableIndex.s_id=$joinfield";
					} else { // exotic case with table that uses subject title+namespace in container object (should never happen in SMW core)
						$from .= " INNER JOIN " . $db->tableName( SMWSql3SmwIds::TABLE_NAME ) . " AS ids$tableIndex ON ids$tableIndex.smw_id=$joinfield" .
						         " INNER JOIN " . $db->tableName( $subproptable->getName() ) . " AS t$tableIndex ON " .
						         "t$tableIndex.s_title=ids$tableIndex.smw_title AND t$tableIndex.s_namespace=ids$tableIndex.smw_namespace";
					}

					if ( !$subproptable->isFixedPropertyTable() ) { // the ID we get should be !=0, so no point in filtering the converse
						$where .= ( $where ? ' AND ' : '' ) . "t$tableIndex.p_id=" . $db->addQuotes( $this->store->smwIds->getSMWPropertyID( $subproperty ) );
					}

					$this->prepareValueQuery( $from, $where, $subproptable, $subvalue, $tableIndex );
				}
			}
		} elseif ( !is_null( $value ) ) { // add conditions for given value
			$diHandler = $this->store->getDataItemHandlerForDIType( $value->getDIType() );
			foreach ( $diHandler->getWhereConds( $value ) as $fieldname => $value ) {
				$where .= ( $where ? ' AND ' : '' ) . "t$tableIndex.$fieldname=" . $db->addQuotes( $value );
			}
		}
	}

	/**
	 * @see SMWStore::getAllPropertySubjects
	 *
	 * @param SMWDIProperty $property
	 * @param SMWRequestOptions $requestOptions
	 *
	 * @return array of DIWikiPage
	 */
	public function getAllPropertySubjects( SMWDIProperty $property, SMWRequestOptions $requestOptions = null ) {
		$result = $this->getPropertySubjects( $property, null, $requestOptions );

		return $result;
	}

	/**
	 * @see Store::getProperties
	 *
	 * @param DIWikiPage $subject
	 * @param SMWRequestOptions|null $requestOptions
	 *
	 * @return SMWDataItem[]
	 */
	public function getProperties( DIWikiPage $subject, SMWRequestOptions $requestOptions = null ) {
		$sid = $this->store->smwIds->getSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName()
		);

		if ( $sid == 0 ) { // no id, no page, no properties
			return array();
		}

		$db = $this->store->getConnection();
		$result = array();

		// potentially need to get more results, since options apply to union
		if ( $requestOptions !== null ) {
			$suboptions = clone $requestOptions;
			$suboptions->limit = $requestOptions->limit + $requestOptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}

		foreach ( $this->store->getPropertyTables() as $propertyTable ) {
			if ( $propertyTable->usesIdSubject() ) {
				$where = 's_id=' . $db->addQuotes( $sid );
			} elseif ( $subject->getInterwiki() === '' ) {
				$where = 's_title=' . $db->addQuotes( $subject->getDBkey() ) . ' AND s_namespace=' . $db->addQuotes( $subject->getNamespace() );
			} else { // subjects with non-empty interwiki cannot have properties
				continue;
			}

			if ( $propertyTable->isFixedPropertyTable() ) {
				// just check if subject occurs in table
				$res = $db->select(
					$propertyTable->getName(),
					'*',
					$where,
					__METHOD__,
					array( 'LIMIT' => 1 )
				);

				if ( $db->numRows( $res ) > 0 ) {
					$result[] = new SMW\DIProperty( $propertyTable->getFixedProperty() );
				}


			} else {
				// select all properties
				$from = $db->tableName( $propertyTable->getName() );

				$from .= " INNER JOIN " . $db->tableName( SMWSql3SmwIds::TABLE_NAME ) . " ON smw_id=p_id";
				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey',
					// (select sortkey since it might be used in ordering (needed by Postgres))
					$where . $this->store->getSQLConditions( $suboptions, 'smw_sortkey', 'smw_sortkey' ),
					__METHOD__, $this->store->getSQLOptions( $suboptions, 'smw_sortkey' ) );
				foreach ( $res as $row ) {
					$result[] = new SMW\DIProperty( $row->smw_title );
				}
			}

			$db->freeResult( $res );
		}

		// apply options to overall result
		$result = $this->store->applyRequestOptions( $result, $requestOptions );


		return $result;
	}

	/**
	 * Implementation of SMWStore::getInProperties(). This function is meant to
	 * be used for finding properties that link to wiki pages.
	 *
	 * @since 1.8
	 * @see SMWStore::getInProperties
	 *
	 * @param SMWDataItem $value
	 * @param SMWRequestOptions|null $requestOptions
	 *
	 * @return array of SMWWikiPageValue
	 */
	public function getInProperties( SMWDataItem $value, SMWRequestOptions $requestOptions = null ) {

		$db = $this->store->getConnection();
		$result = array();

		// Potentially need to get more results, since options apply to union.
		if ( $requestOptions !== null ) {
			$subOptions = clone $requestOptions;
			$subOptions->limit = $requestOptions->limit + $requestOptions->offset;
			$subOptions->offset = 0;
		} else {
			$subOptions = null;
		}

		$diType = $value->getDIType();

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( $diType != $proptable->getDiType() ) {
				continue;
			}

			$where = $from = '';

			if ( $this->traversalPropertyLookup->isEnabledFeature( SMW_ESTORE_IN_PROP ) ) {

				$res = $this->traversalPropertyLookup->fetch(
					$proptable,
					$value,
					$requestOptions
				);

				foreach ( $res as $row ) {
					try {
						$result[] = new SMW\DIProperty( $row->smw_title );
					} catch (SMWDataItemException $e) {
						// has been observed to happen (empty property title); cause unclear; ignore this data
					}
				}

			} elseif ( !$proptable->isFixedPropertyTable() ) { // join ID table to get property titles
				$from = $db->tableName( SMWSql3SmwIds::TABLE_NAME ) . " INNER JOIN " . $db->tableName( $proptable->getName() ) . " AS t1 ON t1.p_id=smw_id";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );

				$where .= " AND smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWDELETEIW );

				$res = $db->select( $from, 'DISTINCT smw_title,smw_sortkey,smw_sort,smw_iw',
						// select sortkey since it might be used in ordering (needed by Postgres)
						$where . $this->store->getSQLConditions( $subOptions, 'smw_sortkey', 'smw_sortkey', $where !== '' ),
						__METHOD__, $this->store->getSQLOptions( $subOptions, 'smw_sort' ) );

				foreach ( $res as $row ) {
					try {
						$result[] = new SMW\DIProperty( $row->smw_title );
					} catch (SMWDataItemException $e) {
						// has been observed to happen (empty property title); cause unclear; ignore this data
					}
				}
			} else {
				$from = $db->tableName( $proptable->getName() ) . " AS t1";
				$this->prepareValueQuery( $from, $where, $proptable, $value, 1 );
				$res = $db->select( $from, '*', $where, __METHOD__, array( 'LIMIT' => 1 ) );

				if ( $db->numRows( $res ) > 0 ) {
					$result[] = new SMW\DIProperty( $proptable->getFixedProperty() );
				}
			}
		}

		$result = $this->store->applyRequestOptions( $result, $requestOptions ); // apply options to overall result

		return $result;
	}

}
