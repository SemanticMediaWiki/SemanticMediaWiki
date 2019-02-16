<?php

namespace SMW;

use SMW\Store;
use SMW\MediaWiki\Collator;
use SMWDataItem as DataItem;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SortLetter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Collator
	 */
	public $collator;

	/**
	* @since 3.1
	*
	* @param Store $store
	* @param Collator $collator
	*/
	public function __construct( Store $store, Collator $collator = null ) {
		$this->store = $store;
		$this->collator = $collator;

		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}
	}

	/**
	* @since 3.1
	*
	* @param DataItem $dataItem
	*
	* @return string
	*/
	public function getFirstLetter( DataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
			$sortKey = $this->store->getWikiPageSortKey( $dataItem );
		}

		return $this->collator->getFirstLetter( $sortKey );
	}

}
