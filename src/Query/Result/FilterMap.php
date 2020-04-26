<?php

namespace SMW\Query\Result;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Store;
use SMW\SQLStore\EntityStore\FieldList;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FilterMap {

	/**
	 * List of properties
	 */
	const PROPERTY_LIST = FieldList::PROPERTY_LIST;

	/**
	 * List of category
	 */
	const CATEGORY_LIST = FieldList::CATEGORY_LIST;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var []
	 */
	private $results = [];

	/**
	 * @var FieldList
	 */
	private $fieldList;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param array $results
	 */
	public function __construct( Store $store, array $results ) {
		$this->store = $store;
		$this->results = $results;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function getCountListByType( string $type ) : array {

		if ( $this->fieldList === null ) {
			$this->fieldList = $this->loadList();
		}

		return $this->fieldList->getCountListByType( $type );
	}

	private function loadList() {
		return $this->fieldList = $this->store->getObjectIds()->preload( $this->results );
	}

}
