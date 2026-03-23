<?php

namespace SMW;

use SMW\DataItems\DataItem;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Collator;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SortLetter {

	private Store $store;

	/**
	 * @var Collator
	 */
	public $collator;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param Collator|null $collator
	 */
	public function __construct( Store $store, ?Collator $collator = null ) {
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

		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE &&
			$dataItem instanceof WikiPage
		) {
			$sortKey = $this->store->getWikiPageSortKey( $dataItem );
		}

		return $this->collator->getFirstLetter( $sortKey );
	}

}
