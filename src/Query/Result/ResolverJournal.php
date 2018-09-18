<?php

namespace SMW\Query\Result;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;

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
class ResolverJournal {

	/**
	 * @var array
	 */
	private $dataItems = [];

	/**
	 * @var array
	 */
	private $properties = [];

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getEntityList() {
		return $this->dataItems;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getPropertyList() {
		return $this->properties;
	}

	/**
	 * @since 2.4
	 */
	public function prune() {
		$this->dataItems = [];
		$this->properties = [];
	}

	/**
	 * @since 2.4
	 *
	 * @param DataItem $dataItem
	 */
	public function recordItem( DataItem $dataItem ) {
		if ( $dataItem instanceof DIWikiPage ) {
			$this->dataItems[$dataItem->getHash()] = $dataItem;
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty|null $property
	 */
	public function recordProperty( DIProperty $property = null ) {
		if ( $property !== null ) {
			$this->properties[$property->getKey()] = $property;
		}
	}

}
