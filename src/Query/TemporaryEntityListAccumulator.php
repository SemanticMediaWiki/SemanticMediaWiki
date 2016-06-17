<?php

namespace SMW\Query;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWQuery as Query;

/**
 * This class records selected entities used in a QueryResult by the time the
 * ResultArray creates an object instance which avoids unnecessary work in the
 * QueryResultDependencyListResolver (in terms of recursive processing of the
 * QueryResult) to find related "column" entities (those related to a
 * printrequest).
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TemporaryEntityListAccumulator {

	/**
	 * @var Query
	 */
	private $query;

	/**
	 * @var string|null
	 */
	private $queryId = null;

	/**
	 * @var array
	 */
	private $entityList = array();

	/**
	 * @since 2.4
	 *
	 * @param Query $query
	 */
	public function __construct( Query $query ) {
		$this->query = $query;
	}

	/**
	 * @since  2.4
	 *
	 * @return string
	 */
	public function getQueryId() {

		if ( $this->queryId === null ) {
			$this->queryId = $this->query->getQueryId();
		}

		return $this->queryId;
	}

	/**
	 * @since  2.4
	 *
	 * @return array
	 */
	public function getEntityList( $queryID = null ) {

		if ( $queryID !== null ) {
			return isset( $this->entityList[$queryID] ) ? $this->entityList[$queryID] : array();
		}

		return $this->entityList;
	}

	/**
	 * @since  2.4
	 *
	 * @param string|null $queryID
	 */
	public function pruneEntityList( $queryID = null ) {

		if ( $queryID === null ) {
			return $this->entityList = array();
		}

		unset( $this->entityList[$queryID] );
	}

	/**
	 * @since  2.4
	 *
	 * @param DIProperty|null $property
	 * @param DataItem $dataItem
	 */
	public function addToEntityList( DIProperty $property = null, DataItem $dataItem ) {

		$queryID = $this->getQueryId();

		if ( !isset( $this->entityList[$queryID] ) ) {
			$this->entityList[$queryID] = array();
		}

		if ( $dataItem instanceof DIWikiPage ) {
			$this->entityList[$queryID][$dataItem->getHash()] = $dataItem;
		}
	}

}
