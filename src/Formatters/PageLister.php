<?php

namespace SMW\Formatters;

use Iterator;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\Localizer\Localizer;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\RequestOptions;
use SMW\StoreFactory;

/**
 * Helper class to generate HTML lists of wiki pages, with support for paged
 * navigation using the from/until and limit settings as in MediaWiki's
 * CategoryPage.
 *
 * The class attempts to allow as much code as possible to be shared among
 * different places where similar lists are used.
 *
 * Some code adapted from CategoryPage.php
 *
 * @ingroup SMW
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class PageLister {

	/**
	 * Constructor
	 */
	public function __construct(
		protected $mDiWikiPages,
		protected $mDiProperty,
		protected $mLimit,
		protected $mFrom = '',
		protected $mUntil = '',
	) {
	}

	/**
	 * Generates the prev/next link part to the HTML code of the top and
	 * bottom section of the page. Whether and how these links appear
	 * depends on specified boundaries, limit, and results. The title is
	 * required to create a link to the right page. The query array gives
	 * optional further parameters to append to all navigation links.
	 */
	public function getNavigationLinks( Title $title, array $query = [] ): string {
		$limitText = Localizer::getInstance()->getUserLanguage()->formatNum( $this->mLimit );

		$resultCount = count( $this->mDiWikiPages );
		$beyondLimit = ( $resultCount > $this->mLimit );

		if ( $this->mUntil !== null && $this->mUntil !== '' ) {
			if ( $beyondLimit ) {
				$first = StoreFactory::getStore()->getWikiPageSortKey( $this->mDiWikiPages[1] );
			} else {
				$first = '';
			}

			$last = $this->mUntil;
		} elseif ( $beyondLimit || ( $this->mFrom !== null && $this->mFrom !== '' ) ) {
			$first = $this->mFrom;

			if ( $beyondLimit ) {
				$last = StoreFactory::getStore()->getWikiPageSortKey( $this->mDiWikiPages[$resultCount - 1] );
			} else {
				$last = '';
			}
		} else {
			return '';
		}

		$prevLink = wfMessage( 'smw-prev', $limitText )->escaped();
		if ( $first !== '' ) {
			$prevLink = $this->makeSelfLink( $title, $prevLink, $query + [ 'until' => $first ] );
		}

		$nextLink = wfMessage( 'smw-next', $limitText )->escaped();
		if ( $last !== '' ) {
			$nextLink = $this->makeSelfLink( $title, $nextLink, $query + [ 'from' => $last ] );
		}

		return "($prevLink) ($nextLink)";
	}

	/**
	 * Format an HTML link with the given text and parameters.
	 */
	protected function makeSelfLink( Title $title, $linkText, array $parameters ): string {
		return smwfGetLinker()->link( $title, $linkText, [], $parameters );
	}

	/**
	 * Make RequestOptions suitable for obtaining a list of results for
	 * the given limit, and from or until string. One more result than the
	 * limit will be created, and the results may have to be reversed in
	 * order if ascending is set to false in the resulting object.
	 */
	public static function getRequestOptions(
		int $limit,
		string $from,
		string $until
	): RequestOptions {
		$options = new RequestOptions();
		$options->limit = $limit + 1;
		$options->sort = true;

		if ( $from !== '' ) {
			$options->boundary = $from;
			$options->ascending = true;
			$options->include_boundary = true;
		} elseif ( $until !== '' ) {
			$options->boundary = $until;
			$options->ascending = false;
			$options->include_boundary = false;
		}

		return $options;
	}

	/**
	 * Make Query suitable for obtaining a list of results based on the
	 * given description, limit, and from or until string. One more result
	 * than the limit will be created, and the results may have to be
	 * reversed in order if $until is nonempty.
	 */
	public static function getQuery(
		Description $description,
		int $limit,
		string $from,
		string $until
	): Query {
		if ( $from !== '' ) {
			$diWikiPage = new WikiPage( $from, NS_MAIN, '' ); // make a dummy wiki page as boundary
			$fromDescription = new ValueDescription( $diWikiPage, null, SMW_CMP_GEQ );
			$queryDescription = new Conjunction( [ $description, $fromDescription ] );
			$order = 'ASC';
		} elseif ( $until !== '' ) {
			$diWikiPage = new WikiPage( $until, NS_MAIN, '' ); // make a dummy wiki page as boundary
			$untilDescription = new ValueDescription( $diWikiPage, null, SMW_CMP_LESS ); // do not include boundary in this case
			$queryDescription = new Conjunction( [ $description, $untilDescription ] );
			$order = 'DESC';
		} else {
			$queryDescription = $description;
			$order = 'ASC';
		}

		$queryDescription->addPrintRequest( new PrintRequest( PrintRequest::PRINT_THIS, '' ) );

		$query = new Query( $queryDescription );
		$query->sortkeys[''] = $order;
		$query->setLimit( $limit + 1 );

		return $query;
	}

	/**
	 * Format a list of data items chunked by letter, either as a
	 * bullet list or a columnar format, depending on the length.
	 */
	public function formatList( int $cutoff = 6 ): string {
		$end = count( $this->mDiWikiPages );
		$start = 0;
		if ( $end > $this->mLimit ) {
			if ( $this->mFrom !== '' ) {
				$end -= 1;
			} else {
				$start += 1;
			}
		}

		if ( count( $this->mDiWikiPages ) > $cutoff ) {
			return self::getColumnList( $start, $end, $this->mDiWikiPages, $this->mDiProperty );
		} elseif ( count( $this->mDiWikiPages ) > 0 ) {
			return self::getShortList( $start, $end, $this->mDiWikiPages, $this->mDiProperty );
		} else {
			return '';
		}
	}

	/**
	 * Format a list of WikiPage objects chunked by letter in a three-column
	 * list, ordered vertically.
	 */
	public static function getColumnList(
		int $start,
		int $end,
		array $diWikiPages,
		?Property $diProperty,
		?callback $moreCallback = null
	): string {
		if ( $diWikiPages instanceof Iterator ) {
			$diWikiPages = iterator_to_array( $diWikiPages );
		}

		// Divide list into three equal chunks.
		$chunk = (int)( ( $end - $start + 1 ) / 3 );

		// Get and display header.
		$r = '<table width="100%"><tr valign="top">';

		$prevStartChar = 'none';

		// Loop through the chunks.
		for ( $startChunk = $start, $endChunk = $chunk, $chunkIndex = 0;
			$chunkIndex < 3;
			++$chunkIndex, $startChunk = $endChunk, $endChunk += $chunk + 1 ) {
			$r .= "<td width='33%'>\n";
			$atColumnTop = true;

			// output all diWikiPages
			for ( $index = $startChunk; $index < $endChunk && $index < $end; ++$index ) {

				if ( !isset( $diWikiPages[$index] ) ) {
					continue;
				}

				$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$index], $diProperty );
				$searchlink = Infolink::newBrowsingLink( '+', $dataValue->getWikiValue() );

				// check for change of starting letter or beginning of chunk
				$sortkey = StoreFactory::getStore()->getWikiPageSortKey( $diWikiPages[$index] );
				$startChar = self::getFirstChar( $sortkey );

				if ( ( $index == $startChunk ) ||
					 ( $startChar != $prevStartChar ) ) {
					if ( $atColumnTop ) {
						$atColumnTop = false;
					} else {
						$r .= "</ul>\n";
					}

					if ( $startChar == $prevStartChar ) {
						$cont_msg = ' ' . wfMessage( 'smw-listingcontinuesabbrev' )->escaped();
					} else {
						$cont_msg = '';
					}

					$r .= "<h3>" . htmlspecialchars( $startChar ) . $cont_msg . "</h3>\n<ul>";

					$prevStartChar = $startChar;
				}

				$r .= "<li>" . $dataValue->getLongHTMLText( smwfGetLinker() ) . '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . "</li>\n";
			}

			if ( $index == $end && $moreCallback !== null ) {
				$r .= "<li>" . call_user_func( $moreCallback ) . "</li>\n";
			}

			if ( !$atColumnTop ) {
				$r .= "</ul>\n";
			}

			$r .= "</td>\n";
		}

		$r .= '</tr></table>';

		return $r;
	}

	/**
	 * Format a list of diWikiPages chunked by letter in a bullet list.
	 */
	public static function getShortList(
		int $start,
		int $end,
		array $diWikiPages,
		?Property $diProperty,
		?callback $moreCallback = null
	): string {
		if ( $diWikiPages instanceof Iterator ) {
			$diWikiPages = iterator_to_array( $diWikiPages );
		}

		$startDv = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$start], $diProperty );
		$searchlink = Infolink::newBrowsingLink( '+', $startDv->getWikiValue() );

		// For a redirect, disable the DisplayTitle to show the original (aka source) page
		if ( $diProperty !== null && $diProperty->getKey() == '_REDI' ) {
			$startDv->setOption( 'smwgDVFeatures', ( $startDv->getOption( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
		}

		$startChar = self::getFirstChar( $diWikiPages[$start] );

		$r = '<h3>' . htmlspecialchars( $startChar ) . "</h3>\n" .
			 '<ul><li>' . $startDv->getLongHTMLText( smwfGetLinker() ) . '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . '</li>';

		$prevStartChar = $startChar;
		for ( $index = $start + 1; $index < $end; $index++ ) {
			$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$index], $diProperty );
			$searchlink = Infolink::newBrowsingLink( '+', $dataValue->getWikiValue() );

			// For a redirect, disable the DisplayTitle to show the original (aka source) page
			if ( $diProperty !== null && $diProperty->getKey() == '_REDI' ) {
				$dataValue->setOption( 'smwgDVFeatures', ( $dataValue->getOption( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
			}

			$startChar = self::getFirstChar( $diWikiPages[$index] );

			if ( $startChar != $prevStartChar ) {
				$r .= "</ul><h3>" . htmlspecialchars( $startChar ) . "</h3>\n<ul>";
				$prevStartChar = $startChar;
			}

			$r .= '<li>' . $dataValue->getLongHTMLText( smwfGetLinker() ) . '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . '</li>';
		}

		if ( $moreCallback !== null ) {
			$r .= '<li>' . call_user_func( $moreCallback ) . '</li>';
		}

		$r .= '</ul>';

		return $r;
	}

	private static function getFirstChar( WikiPage $dataItem ) {
		$contentLanguage = Localizer::getInstance()->getContentLanguage();

		$sortkey = StoreFactory::getStore()->getWikiPageSortKey( $dataItem );

		if ( $sortkey === '' ) {
			$sortkey = $dataItem->getDBKey();
		}

		return $contentLanguage->convert( $contentLanguage->firstChar( $sortkey ) );
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( PageLister::class, 'SMWPageLister' );
