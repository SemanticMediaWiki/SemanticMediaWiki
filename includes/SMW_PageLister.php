<?php

use SMW\Query\PrintRequest;

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
class SMWPageLister {

	protected $mDiWikiPages;
	protected $mDiProperty;
	protected $mLimit;
	protected $mFrom;
	protected $mUntil;

	/**
	 * Constructor
	 *
	 * @param $diWikiPages array of SMWDIWikiPage
	 * @param $diProperty mixed SMWDIProperty that the wikipages are values of, or null
	 * @param $limit integer maximal amount of items to display
	 * @param $from string if the results were selected starting from this string
	 * @param $until string if the results were selected reaching until this string
	 */
	public function __construct( $diWikiPages, $diProperty, $limit, $from = '', $until = '' ) {
		$this->mDiWikiPages = $diWikiPages;
		$this->mDiProperty = $diProperty;
		$this->mLimit = $limit;
		$this->mFrom = $from;
		$this->mUntil = $until;
	}

	/**
	 * Generates the prev/next link part to the HTML code of the top and
	 * bottom section of the page. Whether and how these links appear
	 * depends on specified boundaries, limit, and results. The title is
	 * required to create a link to the right page. The query array gives
	 * optional further parameters to append to all navigation links.
	 *
	 * @param $title Title
	 * @param $query array that associates parameter names to parameter values
	 * @return string
	 */
	public function getNavigationLinks( Title $title, $query = [] ) {
		global $wgLang;

		$limitText = $wgLang->formatNum( $this->mLimit );

		$resultCount = count( $this->mDiWikiPages );
		$beyondLimit = ( $resultCount > $this->mLimit );

		if ( !is_null( $this->mUntil ) && $this->mUntil !== '' ) {
			if ( $beyondLimit ) {
				$first = \SMW\StoreFactory::getStore()->getWikiPageSortKey( $this->mDiWikiPages[1] );
			} else {
				$first = '';
			}

			$last = $this->mUntil;
		} elseif ( $beyondLimit || ( !is_null( $this->mFrom ) && $this->mFrom !== '' ) ) {
			$first = $this->mFrom;

			if ( $beyondLimit ) {
				$last = \SMW\StoreFactory::getStore()->getWikiPageSortKey( $this->mDiWikiPages[$resultCount - 1] );
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
	 *
	 * @return string
	 */
	protected function makeSelfLink( Title $title, $linkText, array $parameters ) {
		return smwfGetLinker()->link( $title, $linkText, [], $parameters );
	}

	/**
	 * Make SMWRequestOptions suitable for obtaining a list of results for
	 * the given limit, and from or until string. One more result than the
	 * limit will be created, and the results may have to be reversed in
	 * order if ascending is set to false in the resulting object.
	 *
	 * @param $limit integer
	 * @param $from string can be empty if no from condition is desired
	 * @param $until string can be empty if no until condition is desired
	 * @return SMWRequestOptions
	 */
	public static function getRequestOptions( $limit, $from, $until ) {
		$options = new SMWRequestOptions();
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
	 * Make SMWQuery suitable for obtaining a list of results based on the
	 * given description, limit, and from or until string. One more result
	 * than the limit will be created, and the results may have to be
	 * reversed in order if $until is nonempty.
	 *
	 * @param $description SMWDescription main query description
	 * @param $limit integer
	 * @param $from string can be empty if no from condition is desired
	 * @param $until string can be empty if no until condition is desired
	 * @return SMWQuery
	 */
	public static function getQuery( SMWDescription $description, $limit, $from, $until ) {
		if ( $from !== '' ) {
			$diWikiPage = new SMWDIWikiPage( $from, NS_MAIN, '' ); // make a dummy wiki page as boundary
			$fromDescription = new SMWValueDescription( $diWikiPage, null, SMW_CMP_GEQ );
			$queryDescription = new SMWConjunction( [ $description, $fromDescription ] );
			$order = 'ASC';
		} elseif ( $until !== '' ) {
			$diWikiPage = new SMWDIWikiPage( $until, NS_MAIN, '' ); // make a dummy wiki page as boundary
			$untilDescription = new SMWValueDescription( $diWikiPage, null, SMW_CMP_LESS ); // do not include boundary in this case
			$queryDescription = new SMWConjunction( [ $description, $untilDescription ] );
			$order = 'DESC';
		} else {
			$queryDescription = $description;
			$order = 'ASC';
		}

		$queryDescription->addPrintRequest( new PrintRequest( PrintRequest::PRINT_THIS, '' ) );

		$query = new SMWQuery( $queryDescription );
		$query->sortkeys[''] = $order;
		$query->setLimit( $limit + 1 );

		return $query;
	}

	/**
	 * Format a list of data items chunked by letter, either as a
	 * bullet list or a columnar format, depending on the length.
	 *
	 * @param $cutoff integer, use columns for more results than that
	 * @return string
	 */
	public function formatList( $cutoff = 6 ) {
		$end = count( $this->mDiWikiPages );
		$start = 0;
		if ( $end > $this->mLimit ) {
			if ( $this->mFrom !== '' ) {
				$end -= 1;
			} else {
				$start += 1;
			}
		}

		if ( count ( $this->mDiWikiPages ) > $cutoff ) {
			return self::getColumnList( $start, $end, $this->mDiWikiPages, $this->mDiProperty );
		} elseif ( count( $this->mDiWikiPages ) > 0 ) {
			return self::getShortList( $start, $end, $this->mDiWikiPages, $this->mDiProperty );
		} else {
			return '';
		}
	}

	/**
	 * Format a list of SMWDIWikiPage objects chunked by letter in a three-column
	 * list, ordered vertically.
	 *
	 * @param $start integer
	 * @param $end integer
	 * @param $diWikiPages array of SMWDIWikiPage
	 * @param $diProperty SMWDIProperty that the wikipages are values of, or null
	 *
	 * @return string
	 */
	public static function getColumnList( $start, $end, $diWikiPages, $diProperty, $moreCallback = null ) {
		global $wgContLang;

		if ( $diWikiPages instanceof \Iterator ) {
			$diWikiPages = iterator_to_array( $diWikiPages );
		}

		// Divide list into three equal chunks.
		$chunk = (int) ( ( $end - $start + 1 ) / 3 );

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

				$dataValue = \SMW\DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$index], $diProperty );
				$searchlink = \SMWInfolink::newBrowsingLink( '+', $dataValue->getWikiValue() );

				// check for change of starting letter or beginning of chunk
				$sortkey = \SMW\StoreFactory::getStore()->getWikiPageSortKey( $diWikiPages[$index] );
				$startChar = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );

				if ( ( $index == $startChunk ) ||
					 ( $startChar != $prevStartChar ) ) {
					if ( $atColumnTop ) {
						$atColumnTop = false;
					} else {
						$r .= "</ul>\n";
					}

					if ( $startChar == $prevStartChar ) {
						$cont_msg = ' ' . wfMessage( 'listingcontinuesabbrev' )->escaped();
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
	 *
	 * @param $start integer
	 * @param $end integer
	 * @param $diWikiPages array of SMWDataItem
	 * @param $diProperty SMWDIProperty that the wikipages are values of, or null
	 *
	 * @return string
	 */
	public static function getShortList( $start, $end, $diWikiPages, $diProperty, $moreCallback = null ) {

		if ( $diWikiPages instanceof \Iterator ) {
			$diWikiPages = iterator_to_array( $diWikiPages );
		}

		$startDv = \SMW\DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$start], $diProperty );
		$searchlink = \SMWInfolink::newBrowsingLink( '+', $startDv->getWikiValue() );

		// For a redirect, disable the DisplayTitle to show the original (aka source) page
		if ( $diProperty !== null && $diProperty->getKey() == '_REDI' ) {
			$startDv->setOption( 'smwgDVFeatures', ( $startDv->getOption( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
		}

		$startChar = self::getFirstChar( $diWikiPages[$start] );

		$r = '<h3>' . htmlspecialchars( $startChar ) . "</h3>\n" .
		     '<ul><li>' . $startDv->getLongHTMLText( smwfGetLinker() ) . '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . '</li>';

		$prevStartChar = $startChar;
		for ( $index = $start + 1; $index < $end; $index++ ) {
			$dataValue = \SMW\DataValueFactory::getInstance()->newDataValueByItem( $diWikiPages[$index], $diProperty );
			$searchlink = \SMWInfolink::newBrowsingLink( '+', $dataValue->getWikiValue() );

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

	private static function getFirstChar( $dataItem ) {
		global $wgContLang;

		$sortkey = \SMW\StoreFactory::getStore()->getWikiPageSortKey( $dataItem );

		if ( $sortkey === '' ) {
			$sortkey = $dataItem->getDBKey();
		}

		return $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );
	}

}
