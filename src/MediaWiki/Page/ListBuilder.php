<?php

namespace SMW\MediaWiki\Page;

use SMW\Store;
use SMW\Message;
use SMW\MediaWiki\Collator;
use SMW\DataValueFactory;
use SMW\DIProperty;
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
	private $store;

	/**
	 * @var Collator
	 */
	private $collator;

	/**
	 * @var callable
	 */
	private $itemFormatter;

	/**
	 * @var DIProperty
	 */
	private $property;

	/**
	 * @var boolean
	 */
	private $isRTL = false;

	/**
	 * @var callable
	 */
	private $lastItemFormatter;

	/**
	 * @var Linker
	 */
	private $linker = false;

	/**
	 * @var integer
	 */
	private $sort = SORT_NATURAL;

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
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 */
	public function setProperty( DIProperty $property ) {
		$this->property = $property;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $isRTL
	 */
	public function isRTL( $isRTL ) {
		$this->isRTL = (bool)$isRTL;
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
	 * @since 3.1
	 *
	 * @param callable $lastItemFormatter
	 */
	public function setLastItemFormatter( callable $lastItemFormatter ) {
		$this->lastItemFormatter = $lastItemFormatter;
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
	public function getColumnList( array $dataItems, $colsThreshold = 10 ) {

		$htmlColumns = new HtmlColumns();

		$htmlColumns->setResponsiveCols();
		$htmlColumns->setResponsiveColsThreshold( $colsThreshold );
		$htmlColumns->setColumns( 2 );
		$htmlColumns->isRTL( $this->isRTL );

		$htmlColumns->setContinueAbbrev(
			Message::get( 'listingcontinuesabbrev', Message::PARSE, Message::USER_LANGUAGE )
		);

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
		$startChar = '';

		// PHP 7.0 (only)
		$itemFormatter = $this->itemFormatter;
		$sortLetter = $this->store->service( 'SortLetter' );

		foreach ( $dataItems as $dataItem ) {

			$dataValue = $dataValueFactory->newDataValueByItem( $dataItem, $this->property );

			// For a redirect, disable the DisplayTitle to show the original (aka source) page
			if ( $this->property !== null && $this->property->getKey() == '_REDI' ) {
				$dataValue->setOption( 'smwgDVFeatures', ( $dataValue->getOption( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
			}

			$startChar = $sortLetter->getFirstLetter( $dataItem );

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

		if ( is_callable( $this->lastItemFormatter ) && $startChar !== '' ) {
			$contents[$startChar][] = call_user_func_array( $this->lastItemFormatter, [] );
		}

		ksort( $contents, $this->sort );

		return $contents;
	}

}
