<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\DataTypeRegistry;
use SMWDataItem as DataItem;
use SMW\MediaWiki\LinkBatch;
use RuntimeException;

/**
 * Prefetch values for a list of known subjects to a specific property to avoid
 * using `Store::getPropertyValues` for each single subject request.
 *
 * It makes use of a "bulk" request by taking advantage of the WHERE IN construct
 * to reduce the amount FETCH (aka SELECT) queries by retrieving values for a
 * specific property and a list of subjects.
 *
 * The raw result list is encoded by a hash and is required to be split by this
 * class to match the correct DataItem representation for a specific subject in
 * list of subjects.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchItemLookup {

	/**
	 * Uses the item hash as index instead of the default ID based index.
	 */
	const HASH_INDEX = 'hash.index';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	/**
	 * @var PropertySubjectsLookup
	 */
	private $propertySubjectsLookup;

	/**
	 * @var LinkBatch
	 */
	private $linkBatch;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param CachingSemanticDataLookup $semanticDataLookup
	 * @param PropertySubjectsLookup $propertySubjectsLookup
	 * @param LinkBatch|null $LinkBatch
	 */
	public function __construct( Store $store, CachingSemanticDataLookup $semanticDataLookup, PropertySubjectsLookup $propertySubjectsLookup, LinkBatch $linkBatch = null ) {
		$this->store = $store;
		$this->semanticDataLookup = $semanticDataLookup;
		$this->propertySubjectsLookup = $propertySubjectsLookup;

		// Help reduce the amount of queries by allowing to prefetch those
		// links we know will be used for the display
		if ( $this->linkBatch === null ) {
			$this->linkBatch = LinkBatch::singleton();
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $subjects
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return []
	 */
	public function getPropertyValues( array $subjects, DIProperty $property, RequestOptions $requestOptions ) {

		$this->linkBatch->setCaller( __METHOD__ );
		$this->linkBatch->addFromList( $subjects );
		$this->linkBatch->execute();

		if ( $property->isInverse() ) {
			return $this->prefetchPropertySubjects( $subjects, $property, $requestOptions );
		}

		return $this->prefetchSemanticData( $subjects, $property, $requestOptions );
	}

	private function prefetchSemanticData( array $subjects, DIProperty $property, RequestOptions $requestOptions ) {

		$tableid = $this->store->findPropertyTableID( $property );
		$entityIdManager = $this->store->getObjectIds();

		$proptables = $this->store->getPropertyTables();

		if ( $tableid === '' || !isset( $proptables[$tableid] ) ) {
			return [];
		}

		$propTable = $proptables[$tableid];
		$result = [];

		// In prefetch mode avoid restricting the result due to use of WHERE IN
		$requestOptions->exclude_limit = true;
		$requestOptions->setCaller( __METHOD__ );

		$data = $this->semanticDataLookup->prefetchDataFromTable(
			$subjects,
			$property,
			$propTable,
			$requestOptions
		);

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$propTable->getDiType()
		);

		foreach ( $data as $sid => $itemList ) {

			// SID, the caller is responsible for reassigning the
			// results to a corresponding output
			$hash = $sid;

			if ( $requestOptions->getOption( self::HASH_INDEX ) ) {
				$subject = $entityIdManager->getDataItemById(
					$sid
				);

				// Subject hash is used as identifying hash to split
				// the collected set of values
				$hash = $subject->getHash();

				// Avoid reference to something like `__foo_bar#102##` (predefined property)
				if ( $subject->getNamespace() === SMW_NS_PROPERTY && $hash[0] === '_' ) {
					$property = DIProperty::newFromUserLabel(
						$subject->getDBKey()
					);
					$hash = $property->getCanonicalDIWikiPage()->getHash();
				}
			}

			if ( !isset( $result[$hash] ) ) {
				$result[$hash] = [];
			}

			$sequenceMap = $entityIdManager->getSequenceMap(
				$sid,
				$property->getKey()
			);

			// List of subjects (index which is either the ID or hash) with its
			// corresponding items
			// [
			//  42 => [ DIBlob, DIBlob, ... ],
			//  1001 => [ ... ]
			// ]
			$result[$hash] = $this->buildLIST( $diHandler, $itemList, $requestOptions, $sequenceMap );
		}

		return $result;
	}

	private function prefetchPropertySubjects( array $subjects, DIProperty $property, RequestOptions $requestOptions ) {

		$noninverse = new DIProperty(
			$property->getKey(),
			false
		);

		$type = DataTypeRegistry::getInstance()->getDataItemByType(
			$noninverse->findPropertyTypeID()
		);

		$tableid = $this->store->findPropertyTableID( $noninverse );
		$idTable = $this->store->getObjectIds();

		if ( $tableid === '' ) {
			return [];
		}

		$proptables = $this->store->getPropertyTables();
		$ids = [];

		foreach ( $subjects as $subject ) {
			$sid = $idTable->getSMWPageID(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$subject->getSubobjectName(),
				true
			);

			if ( $type !== $subject->getDIType() || $sid == 0 ) {
				continue;
			}

			$subject->setId( $sid );
			$ids[] = $sid;
		}

		// In prefetch mode avoid restricting the result due to use of WHERE IN
		$requestOptions->exclude_limit = true;
		$requestOptions->setCaller( __METHOD__ );

		$propTable = $proptables[$tableid];

		$result = $this->propertySubjectsLookup->prefetchFromTable(
			$ids,
			$property,
			$propTable,
			$requestOptions
		);

		return $result;
	}

	private function buildLIST( $diHandler, $itemList, $requestOptions, $sequenceMap ) {

		$values = [];
		$i = 0;

		// Post-processing of the limit/offset, +1 as look ahead
		$limit = ( $requestOptions->limit + $requestOptions->offset ) + 1;
		$offset = $requestOptions->offset;

		// Flip the array to get access to the hash keys as lookup index,
		// the array value defines the position of the annotation value
		// in the list
		if ( $sequenceMap !== [] ) {
			$sequenceMap = array_flip( $sequenceMap );
		}

		foreach ( $itemList as $k => $dbkeys ) {

			// When working with an sequence.map, first go through all matches
			// without limiting the set to ensure it is ordered before
			// in a second step the limit restriction is applied
			if ( $limit > 0 && $i > $limit && $sequenceMap === [] ) {
				break;
			}

			try {
				$dataItem = $diHandler->newFromDBKeys( $dbkeys );
			} catch ( \SMWDataItemException $e ) {
				// maybe type assignment changed since data was stored;
				// don't worry, but we can only drop the data here
				continue;
			}

			$index_hash = md5( $dataItem->getHash() );

			if ( isset( $sequenceMap[$index_hash] ) ) {
				$values[$sequenceMap[$index_hash]] = $dataItem;
			} else {
				$values[$index_hash] = $dataItem;
			}

			$i++;
		}

		// Sort by key to restore the sorting preference that is part of
		// the `sequence_map`
		if ( $sequenceMap !== [] ) {
			ksort( $values );

			// Apply the limit/offset
			if ( $limit > 0 ) {
				$values = array_slice( $values, $offset, $limit, true );
			}
		}

		return $values;
	}

}
