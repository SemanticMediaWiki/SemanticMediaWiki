<?php

namespace SMW\Query\Result;

use SMW\SQLStore\EntityStore\FieldList;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
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
	 * @var FieldList
	 */
	private $fieldList;

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly array $results,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function getCountListByType( string $type ): array {
		if ( $this->fieldList === null ) {
			$this->fieldList = $this->loadList();
		}

		return $this->fieldList->getCountListByType( $type );
	}

	private function loadList() {
		$this->fieldList = $this->store->getObjectIds()->preload( $this->results );
		return $this->fieldList;
	}

}
