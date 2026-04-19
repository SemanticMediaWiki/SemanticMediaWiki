<?php

namespace SMW\MediaWiki\Page;

use MediaWiki\Linker\Linker;
use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\Formatters\Infolink;
use SMW\Localizer\Message;
use SMW\MediaWiki\Collator;
use SMW\Store;
use SMW\Utils\HtmlColumns;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilder {

	/** @var callable */
	private $itemFormatter;

	private ?Property $property = null;

	private bool $isRTL = false;

	/** @var callable */
	private $lastItemFormatter;

	private Linker|false $linker = false;

	private int $sort = SORT_NATURAL;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Store $store,
		private ?Collator $collator = null,
	) {
	}

	/**
	 * @since 3.1
	 */
	public function setProperty( Property $property ): void {
		$this->property = $property;
	}

	/**
	 * @since 3.1
	 */
	public function isRTL( bool $isRTL ): void {
		$this->isRTL = $isRTL;
	}

	/**
	 * @since 3.0
	 */
	public function setItemFormatter( callable $itemFormatter ): void {
		$this->itemFormatter = $itemFormatter;
	}

	/**
	 * @since 3.1
	 *
	 * @param callable $lastItemFormatter
	 */
	public function setLastItemFormatter( callable $lastItemFormatter ): void {
		$this->lastItemFormatter = $lastItemFormatter;
	}

	/**
	 * @since 3.0
	 */
	public function setLinker( Linker|false $linker ): void {
		$this->linker = $linker;
	}

	/**
	 * @since 3.0
	 */
	public function sort( int $sort ): void {
		$this->sort = $sort;
	}

	/**
	 * @since 3.0
	 */
	public function getList( array $dataItems ): array {
		return $this->buildList( $dataItems );
	}

	/**
	 * @since 3.0
	 */
	public function getColumnList( array $dataItems, int $colsThreshold = 10 ): string {
		$htmlColumns = new HtmlColumns();

		$htmlColumns->setResponsiveCols();
		$htmlColumns->setResponsiveColsThreshold( $colsThreshold );
		$htmlColumns->setColumns( 2 );
		$htmlColumns->isRTL( $this->isRTL );

		$htmlColumns->setContinueAbbrev(
			Message::get( 'smw-listingcontinuesabbrev', Message::PARSE, Message::USER_LANGUAGE )
		);

		$htmlColumns->setContents(
			$this->buildList( $dataItems ),
			HtmlColumns::INDEXED_LIST
		);

		return $htmlColumns->getHtml();
	}

	private function buildList( array $dataItems ): array {
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
