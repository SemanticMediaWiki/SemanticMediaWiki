<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Enum;
use SMW\EntityLookup as IEntityLookup;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\DataTypeRegistry;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\Exception\DataItemException;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookup implements IEntityLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

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

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 * @param SQLStoreFactory $factory
	 */
	public function __construct( SQLStore $store, SQLStoreFactory $factory ) {
		$this->store = $store;
		$this->traversalPropertyLookup = $factory->newTraversalPropertyLookup();
		$this->propertySubjectsLookup = $factory->newPropertySubjectsLookup();
		$this->propertiesLookup = $factory->newPropertiesLookup();
		$this->semanticDataLookup = $factory->newSemanticDataLookup();
	}

	/**
	 * @see Store::getSemanticData
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {

		$idTable = $this->store->getObjectIds();

		// *** Find out if this subject exists ***//
		$sortKey = '';

		$sid = $idTable->getSMWPageIDandSort(
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
			return new SemanticData( $subject );
		}

		$propertyTableHashes = $idTable->getPropertyTableHashes( $sid );
		$opts = null;
		$semanticData = null;

		if ( $filter instanceof RequestOptions ) {
			$semanticData = $this->semanticDataLookup->newStubSemanticData( $subject );
		}

		foreach ( $this->store->getPropertyTables() as $tid => $proptable ) {
			if ( !array_key_exists( $proptable->getName(), $propertyTableHashes ) ) {
				continue;
			}

			if ( $filter instanceof RequestOptions ) {
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

		if ( $semanticData->getSequenceMap() === [] ) {
			$semanticData->setSequenceMap(
				$sid,
				$this->store->getObjectIds()->getSequenceMap( $sid )
			);
		}

		// Avoid adding a sortkey for an already extended stub
		if ( !$semanticData->hasProperty( new DIProperty( '_SKEY' ) ) ) {
			$semanticData->addPropertyStubValue( '_SKEY', [ '', $sortKey ] );
		}

		$idTable->warmUpCache(
			$semanticData->getProperties()
		);

		$idTable->warmUpCache(
			$semanticData->getPropertyValues( new DIProperty( '_INST' ) )
		);

		return $semanticData;
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getProperties( DIWikiPage $subject, RequestOptions $requestOptions = null ) {

		$idTable = $this->store->getObjectIds();

		$sid = $idTable->getSMWPageID(
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

		$propertyTableHashes = $idTable->getPropertyTableHashes( $sid );

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
		$idTable->warmUpCache( $result );

		return $result;
	}


	/**
	 * @see Store::getPropertyValues
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, RequestOptions $requestOptions = null ) {

		$idTable = $this->store->getObjectIds();

		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = new DIProperty( $property->getKey(), false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestOptions );
		} elseif ( $subject !== null ) { // subject given, use semantic data cache
			$sid = $idTable->getSMWPageID(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$subject->getSubobjectName(),
				true
			);

			if ( $sid == 0 ) {
				$result = [];
			} elseif ( $property->getKey() == '_SKEY' ) {
				$idTable->getSMWPageIDandSort(
					$subject->getDBkey(),
					$subject->getNamespace(),
					$subject->getInterwiki(),
					$subject->getSubobjectName(),
					$sortKey,
					true
				);

				$sortKeyDi = new DIBlob( $sortKey );
				$result = $this->store->applyRequestOptions( [ $sortKeyDi ], $requestOptions );
			} else {
				$propTableId = $this->store->findPropertyTableID(
					$property
				);

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
				$idTable->warmUpCache( $pv );

				$result = $this->store->applyRequestOptions(
					$pv,
					$requestOptions
				);
			}
		} else { // no subject given, get all values for the given property
			$pid = $idTable->getSMWPropertyID( $property );
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

			$propertyDiId = DataTypeRegistry::getInstance()->getDataItemId(
				$propertyTypeId
			);

			foreach ( $data as $dbkeys ) {
				try {
					$diHandler = $this->store->getDataItemHandlerForDIType( $propertyDiId );
					$result[] = $diHandler->dataItemFromDBKeys( $dbkeys );
				} catch ( DataItemException $e ) {
					// maybe type assignment changed since data was stored;
					// don't worry, but we can only drop the data here
				}
			}
		}

		$idTable->warmUpCache( $result );

		return $result;
	}

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, RequestOptions $requestOptions = null ) {

		// * @todo This method cannot retrieve subjects for sortkeys, i.e., for
		// * property _SKEY. Only empty arrays will be returned there.

		// inverses are working differently
		if ( $property->isInverse() ) {
			$noninverse = new DIProperty( $property->getKey(), false );
			$result = $this->getPropertyValues( $dataItem, $noninverse, $requestOptions );
			return $result;
		}

		$idTable = $this->store->getObjectIds();

		$type = DataTypeRegistry::getInstance()->getDataItemByType(
			$property->findPropertyTypeID()
		);

		// #1222, Filter those where types don't match (e.g property = _txt
		// and value = _wpg)
		if ( $dataItem !== null && $type !== $dataItem->getDIType() ) {
			return [];
		}

		// First build $select, $from, and $where for the DB query
		$pid = $idTable->getSMWPropertyID( $property );
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

		$idTable->warmUpCache( $result );

		return $result;
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getAllPropertySubjects( DIProperty $property, RequestOptions $requestOptions = null  ) {
		return $this->getPropertySubjects( $property, null, $requestOptions );
	}

	/**
	 * @see Store::getInProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getInProperties( DataItem $object, RequestOptions $requestOptions = null ) {

		$result = [];
		$diType = $object->getDIType();

		foreach ( $this->store->getPropertyTables() as $proptable ) {

			if ( $diType != $proptable->getDiType() ) {
				continue;
			}

			$res = $this->traversalPropertyLookup->fetchFromTable(
				$proptable,
				$object,
				$requestOptions
			);

			foreach ( $res as $row ) {
				try {
					$result[] = new DIProperty( $row->smw_title );
				} catch ( DataItemException $e) {
					// has been observed to happen (empty property title); cause unclear; ignore this data
				}
			}
		}

		// Apply options to overall result
		$result = $this->store->applyRequestOptions( $result, $requestOptions );
		$this->store->getObjectIds()->warmUpCache( $result );

		return $result;
	}

}
