<?php

namespace SMW\Page;

use SMW\Store;
use SMW\Message;
use SMW\MediaWiki\Collator;
use SMW\DataValueFactory;
use SMWInfolink as Infolink;
use SMWDataItem as DataItem;
use SMW\Utils\HtmlColumns;
use Linker;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilder {

	/**
	 * @var Store
	 */
	public $store;

	/**
	 * @var Collator
	 */
	public $collator;

	/**
	 * @var callable
	 */
	public $itemFormatter;

	/**
	 * @var Linker
	 */
	public $linker = false;

	/**
	 * @var integer
	 */
	public $sort = SORT_NATURAL;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Collator|null $collator
	 */
	public function __construct( Store $store, Collator $collator = null ) {
		$this->store = $store;
		$this->collator = $collator;
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $itemFormatter
	 */
	public function setItemFormatter( callable $itemFormatter ) {
		$this->itemFormatter = $itemFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * @param Linker|false $linker
	 */
	public function setLinker( $linker ) {
		$this->linker = $linker;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $sort
	 */
	public function sort( $sort ) {
		$this->sort = $sort;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage[] $dataItems
	 *
	 * @return array
	 */
	public function getList( array $dataItems ) {
		return $this->buildList( $dataItems );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage[] $dataItems
	 *
	 * @return string
	 */
	public function getColumnList( array $dataItems ) {

		$htmlColumns = new HtmlColumns();

		if ( count( $dataItems ) > 10 ) {
			$htmlColumns->setColumnClass( 'smw-column-responsive' );
		}

		$htmlColumns->setContinueAbbrev(
			Message::get( 'listingcontinuesabbrev', Message::PARSE, Message::USER_LANGUAGE )
		);

		$htmlColumns->setColumns( 1 );

		$htmlColumns->setContents(
			$this->buildList( $dataItems ),
			HtmlColumns::INDEXED_LIST
		);

		return $htmlColumns->getHtml();
	}

	private function buildList( $dataItems ) {

		$dataValueFactory = DataValueFactory::getInstance();

		if ( $this->linker === false ) {
			$this->linker = smwfGetLinker();
		}

		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}

		$contents = [];

		foreach ( $dataItems as $dataItem ) {

			$dataValue = $dataValueFactory->newDataValueByItem( $dataItem, null );
			$startChar = $this->getFirstLetter( $dataItem );

			if ( $startChar === '' ) {
				$startChar = '...';
			}

			if ( !isset( $contents[$startChar] ) ) {
				$contents[$startChar] = [];
			}

			if ( is_callable( $this->itemFormatter ) ) {
				// Use of ( ... )( ) only possible with PHP7
				// $contents[$startChar][] = ( $this->itemFormatter )( $dataValue, $this->linker );
				$contents[$startChar][] = call_user_func_array( $this->itemFormatter, [ $dataValue, $this->linker ] );
			} else {
				$searchlink = Infolink::newBrowsingLink( '+', $dataValue->getWikiValue() );
				$contents[$startChar][] = $dataValue->getLongHTMLText( $this->linker ) . '&#160;' . $searchlink->getHTML( $this->linker );
			}
		}

		ksort( $contents, $this->sort );

		return $contents;
	}

	private function getFirstLetter( DataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
			$sortKey = $this->store->getWikiPageSortKey( $dataItem );
		}

		return $this->collator->getFirstLetter( $sortKey );
	}

}
