<?php

namespace SMW\Query\Result;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;

/**
 * This class records selected entities used in a QueryResult by the time the
 * ResultArray creates an object instance which avoids unnecessary work in the
 * QueryResultDependencyListResolver (in terms of recursive processing of the
 * QueryResult) to find related "column" entities (those related to a
 * printrequest).
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class ItemJournal {

	private $dataItems = [];
	private $properties = [];

	/**
	 * @since 2.4
	 *
	 * @return DataItem[]
	 */
	public function getEntityList() {
		return $this->dataItems;
	}

	/**
	 * @since 3.0
	 *
	 * @return Property[]
	 */
	public function getPropertyList() {
		return $this->properties;
	}

	/**
	 * @since 2.4
	 */
	public function prune(): void {
		$this->dataItems = [];
		$this->properties = [];
	}

	/**
	 * @since 2.4
	 *
	 * @param DataItem $dataItem
	 */
	public function recordItem( DataItem $dataItem ): void {
		if ( $dataItem instanceof WikiPage ) {
			$this->dataItems[$dataItem->getHash()] = $dataItem;
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param Property|null $property
	 */
	public function recordProperty( ?Property $property = null ): void {
		if ( $property !== null ) {
			$this->properties[$property->getKey()] = $property;
		}
	}

}
