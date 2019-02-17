<?php

use SMW\ApplicationFactory;
use SMW\DataTypeRegistry;
use SMW\Enum;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableDefinition;

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
	 * @var PropertySubjectsLookup
	 */
	private $propertySubjectsLookup;

	/**
	 * @var PropertiesLookup
	 */
	private $propertiesLookup;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	public function __construct( SMWSQLStore3 $parentStore, $factory ) {
		$this->store = $parentStore;
		$this->factory = $factory;
		$this->traversalPropertyLookup = $this->factory->newTraversalPropertyLookup();
		$this->propertySubjectsLookup = $this->factory->newPropertySubjectsLookup();
		$this->propertiesLookup = $this->factory->newPropertiesLookup();
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
		$opts = null;
		$semanticData = null;

		if ( $filter instanceof SMWRequestOptions ) {
			$semanticData = $this->semanticDataLookup->newStubSemanticData( $subject );
		}

		foreach ( $this->store->getPropertyTables() as $tid => $proptable ) {
			if ( !array_key_exists( $proptable->getName(), $propertyTableHashes ) ) {
				continue;
			}

			if ( $filter instanceof SMWRequestOptions ) {
				$opts = $filter;
			} elseif ( $filter !== false ) {
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

			$data = $this->semanticDataLookup->getSemanticData( $sid, $subject, $proptable, $opts );

			if ( $semanticData !== null ) {
				$semanticData->importDataFrom( $data );
			}
		}

		if ( $semanticData === null ) {
			// Note: the sortkey is always set but belongs to no property table,
			// hence no entry in $this->store->m_sdstate[$sid] is made.
			$this->semanticDataLookup->lockCache();
			$this->semanticDataLookup->initLookupCache( $sid, $subject );

			$semanticData = $this->semanticDataLookup->getSemanticDataById(
				$sid
			);

			$this->semanticDataLookup->unlockCache();
		}

		// Avoid adding a sortkey for an already extended stub
		if ( !$semanticData->hasProperty( new DIProperty( '_SKEY' ) ) ) {
			$semanticData->addPropertyStubValue( '_SKEY', [ '', $sortKey ] );
		}

		$this->store->smwIds->warmUpCache(
			$semanticData->getProperties()
		);

		$this->store->smwIds->warmUpCache(
			$semanticData->getPropertyValues( new DIProperty( '_INST' ) )
		);

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
				$result = [];
			} elseif ( $property->getKey() == '_SKEY' ) {
				$this->store->smwIds->getSMWPageIDandSort( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				$subject->getSubobjectName(), $sortKey, true );
				$sortKeyDi = new SMWDIBlob( $sortKey );
				$result = $this->store->applyRequestOptions( [ $sortKeyDi ], $requestOptions );
			} else {
				$propTableId = $this->store->findPropertyTableID( $property );
				$proptables =  $this->store->getPropertyTables();

				if ( !isset( $proptables[$propTableId] ) ) {
					return [];
				}

				$propertyTableDef = $proptables[$propTableId];

				$opts = $this->semanticDataLookup->newRequestOptions(
					$propertyTableDef,
					$property,
					$requestOptions
				);

				$semanticData = $this->semanticDataLookup->getSemanticData(
					$sid,
					$subject,
					$propertyTableDef,
					$opts
				);

				$pv = $semanticData->getPropertyValues( $property );
				$this->store->smwIds->warmUpCache( $pv );

				$result = $this->store->applyRequestOptions(
					$pv,
					$requestOptions
				);
			}
		} else { // no subject given, get all values for the given property
			$pid = $this->store->smwIds->getSMWPropertyID( $property );
			$tableid =  $this->store->findPropertyTableID( $property );

			if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
				return [];
			}

			$proptables =  $this->store->getPropertyTables();
			$data = $this->semanticDataLookup->fetchSemanticDataFromTable(
				$pid,
				$property,
				$proptables[$tableid],
				$requestOptions
			);

			$result = [];
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

		$this->store->smwIds->warmUpCache( $result );

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
	public function getPropertySubjects( SMWDIProperty $property, SMWDataItem $dataItem = null, SMWRequestOptions $requestOptions = null ) {

		// inverses are working differently
		if ( $property->isInverse() ) {
			$noninverse = new SMW\DIProperty( $property->getKey(), false );
			$result = $this->getPropertyValues( $dataItem, $noninverse, $requestOptions );
			return $result;
		}

		$type = DataTypeRegistry::getInstance()->getDataItemByType(
			$property->findPropertyTypeID()
		);

		// #1222, Filter those where types don't match (e.g property = _txt
		// and value = _wpg)
		if ( $dataItem !== null && $type !== $dataItem->getDIType() ) {
			return [];
		}

		// First build $select, $from, and $where for the DB query
		$pid = $this->store->smwIds->getSMWPropertyID( $property );
		$tableid = $this->store->findPropertyTableID( $property );

		if ( ( $pid == 0 ) || ( $tableid === '' ) ) {
			return [];
		}

		$proptables =  $this->store->getPropertyTables();
		$proptable = $proptables[$tableid];

		$result = $this->propertySubjectsLookup->fetchFromTable(
			$pid,
			$proptable,
			$dataItem,
			$requestOptions
		);

		// Keep the result as iterator which is normally advised when the result
		// size is expected to be larger than 1000 or results are retrieved through
		// a job which may process them in batches.
		if ( $requestOptions !== null && $requestOptions->getOption( Enum::SUSPEND_CACHE_WARMUP ) ) {
			return $result;
		}

		$this->store->smwIds->warmUpCache( $result );

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
		return $this->getPropertySubjects( $property, null, $requestOptions );
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

		// no id, no page, no properties
		if ( $sid == 0 ) {
			return [];
		}

		$subject->setId( $sid );
		$result = [];

		// Potentially need to get more results, since options apply to union
		$lookupOptions = $this->propertiesLookup->newRequestOptions(
			$requestOptions
		);

		$propertyTableHashes = $this->store->smwIds->getPropertyTableHashes( $sid );

		foreach ( $this->store->getPropertyTables() as $tid => $propertyTable ) {

			if ( !array_key_exists( $propertyTable->getName(), $propertyTableHashes ) ) {
				continue;
			}

			$res = $this->propertiesLookup->fetchFromTable(
				$subject,
				$propertyTable,
				$lookupOptions
			);

			foreach ( $res as $row ) {
				$result[] = new DIProperty(
					isset( $row->smw_title ) ? $row->smw_title : $row
				);
			}
		}

		// apply options to overall result
		$result = $this->store->applyRequestOptions( $result, $requestOptions );
		$this->store->smwIds->warmUpCache( $result );

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
	 * @return DIProperty[]
	 */
	public function getInProperties( SMWDataItem $value, SMWRequestOptions $requestOptions = null ) {

		$result = [];
		$diType = $value->getDIType();

		foreach ( $this->store->getPropertyTables() as $proptable ) {

			if ( $diType != $proptable->getDiType() ) {
				continue;
			}

			$res = $this->traversalPropertyLookup->fetchFromTable(
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
		}

		// Apply options to overall result
		$result = $this->store->applyRequestOptions( $result, $requestOptions );
		$this->store->smwIds->warmUpCache( $result );

		return $result;
	}

}
