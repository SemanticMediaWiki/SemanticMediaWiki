<?php

/**
 * Abstract subclass of MediaWiki's Article that handles the common tasks of
 * article pages for Types and Properties. Mostly, it implements general processing
 * and the generation of suitable navigation links from results sets and HTTP
 * parameters.
 * 
 * Some code adapted from CategoryPage.php
 * 
 * @file SMW_OrderedListPage.php 
 * @ingroup SMW
 * 
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @author Jeroen De Dauw 
 */
abstract class SMWOrderedListPage extends Article {

	/**
	 * Limit for results per page.
	 * 
	 * @var integer
	 */
	protected $limit;

	/**
	 * Start string: print $limit results from here.
	 * 
	 * @var string
	 */
	protected $from;
	
	/**
	 * End string: print $limit results strictly before this article.
	 * 
	 * @var string
	 */	
	protected $until;

	/**
	 * Array of SMWDIWikiPage objects for which information is printed
	 * (primary ordering method).
	 * 
	 * @var array of SMWDIWikiPage
	 */
	protected $diWikiPages; 
	
	/**
	 * Cache for the current skin, obtained from $wgUser.
	 * 
	 * @var Skin
	 */
	protected $skin;

	/**
	 * Property that the displayed values are for, if any.
	 *
	 * @var SMWDIProperty
	 */
	protected $mProperty = null;

	/**
	 * Overwrite view() from Article.php to add additional html to the output.
	 */
	public function view() {
		global $wgRequest, $wgUser;

		// Copied from CategoryPage
		$diff = $wgRequest->getVal( 'diff' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );
		
		if ( isset( $diff ) && $diffOnly ) {
			return Article::view();
		}

		Article::view();
		$this->showList();
	}

	/**
	 * Main method for addig all additional HTML to the output stream.
	 */
	protected function showList() {
		wfProfileIn( __METHOD__ . ' (SMW)' );
		
		global $wgOut, $wgRequest;
		
		$this->from = $wgRequest->getVal( 'from' );
		$this->until = $wgRequest->getVal( 'until' );
		
		if ( $this->initParameters() ) {
			$wgOut->addHTML( $this->getHTML() );
			SMWOutputs::commitToOutputPage( $wgOut ); // Flush required CSS to output
		}
		
		wfProfileOut( __METHOD__ . ' (SMW)' );
	}

	/**
	 * Initialise some parameters that might be changed by subclasses
	 * (e.g. $limit). Method can be overwritten in this case.
	 * If the method returns false, nothing will be printed besides
	 * the original article.
	 * 
	 * @return true
	 */
	protected function initParameters() {
		$this->limit = 20;
		return true;
	}

	/**
	 * Returns HTML which is added to wgOut.
	 * 
	 * @return string
	 */
	protected function getHTML() {
		$this->clearPageState();
		$this->doQuery();
		$r = "<br id=\"smwfootbr\"/>\n" . $this->getPages();
		
		return $r;
	}

	/**
	 * Initialise internal data structures.
	 */
	protected function clearPageState() {
		$this->diWikiPages = array();
	}

	/**
	 * Execute the DB query and fill the diWikiPages array.
	 * Implemented by subclasses.
	 */
	protected abstract function doQuery();

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which
	 * shall be shown.
	 */
	protected abstract function getPages();

	/**
	 * Generates the prev/next link part to the HTML code of the top and bottom section of the page.
	 */
	protected function getNavigationLinks( $query = array() ) {
		global $wgLang;
		
		$sk = $this->getSkin();
		$limitText = $wgLang->formatNum( $this->limit );

		$ac = count( $this->diWikiPages );

		if ( $this->until != '' ) {
			if ( $ac > $this->limit ) { // (we assume that limit is at least 1)
				$first = smwfGetStore()->getWikiPageSortKey( $this->diWikiPages[1] );
			} else {
				$first = '';
			}

			$last = $this->until;
		} elseif ( ( $ac > $this->limit ) || ( $this->from != '' ) ) {
			$first = $this->from;

			if ( $ac > $this->limit ) {
				$last = smwfGetStore()->getWikiPageSortKey( $this->diWikiPages[$ac - 1] );
			} else {
				$last = '';
			}
		} else {
			return '';
		}

		$prevLink = htmlspecialchars( wfMsg( 'prevn', $limitText ) );
		$this->mTitle->setFragment( '#SMWResults' ); // Make navigation point to the result list.
		
		if ( $first != '' ) {
			$prevLink = $sk->makeLinkObj( $this->mTitle, $prevLink,
				wfArrayToCGI( $query + array( 'until' => $first ) ) );
		}
		$nextLink = htmlspecialchars( wfMsg( 'nextn', $limitText ) );
		
		if ( $last != '' ) {
			$nextLink = $sk->makeLinkObj( $this->mTitle, $nextLink,
				wfArrayToCGI( $query + array( 'from' => $last ) ) );
		}
		
		return "($prevLink) ($nextLink)";
	}

	/**
	 * Fetch and return the relevant skin object.
	 * 
	 * @return Skin
	 */
	protected function getSkin() {
		if ( !$this->skin ) {
			global $wgUser;
			$this->skin = $wgUser->getSkin();
		}
		
		return $this->skin;
	}

	/**
	 * Like Article's getTitle(), but returning a suitable SMWDIWikiPage.
	 * 
	 * @return SMWDIWikiPage
	 */
	protected function getDataItem() {
		$title = $this->getTitle();
		return new SMWDIWikiPage( $title->getDBKey(), $title->getNamespace(), $title->getInterwiki() );
	}

	/**
	 * Format a list of SMWDIWikiPage objects chunked by letter in a three-column
	 * list, ordered vertically.
	 * 
	 * @param $start integer
	 * @param $end integer
	 * @param $diWikiPages of SMWDIWikiPage
	 * 
	 * @return string
	 */
	protected function columnList( $start, $end, $diWikiPages ) {
		global $wgContLang;
		
		// Divide list into three equal chunks.
		$chunk = (int) ( ( $end - $start + 1 ) / 3 );

		// Get and display header.
		$r = '<table width="100%"><tr valign="top">';

		$prev_start_char = 'none';

		// Loop through the chunks.
		for ( $startChunk = $start, $endChunk = $chunk, $chunkIndex = 0;
			$chunkIndex < 3;
			$chunkIndex++, $startChunk = $endChunk, $endChunk += $chunk + 1 ) {
			$r .= "<td>\n";
			$atColumnTop = true;

			// output all diWikiPages
			for ( $index = $startChunk ; $index < $endChunk && $index < $end; $index++ ) {
				$dataValue = SMWDataValueFactory::newDataItemValue( $diWikiPages[$index], $this->mProperty );
				// check for change of starting letter or begining of chunk
				$sortkey = smwfGetStore()->getWikiPageSortKey( $diWikiPages[$index] );
				$start_char = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );
				
				if ( ( $index == $startChunk ) ||
					 ( $start_char != $prev_start_char ) ) {
					if ( $atColumnTop ) {
						$atColumnTop = false;
					} else {
						$r .= "</ul>\n";
					}
					
					$cont_msg = "";
					
					if ( $start_char == $prev_start_char ) {
						$cont_msg = wfMsgHtml( 'listingcontinuesabbrev' );
					}
					
					$r .= "<h3>" . htmlspecialchars( $start_char ) . " $cont_msg</h3>\n<ul>";
					$prev_start_char = $start_char;
				}
				
				$r .= "<li>" . $dataValue->getLongHTMLText( $this->getSkin() ) . "</li>\n";
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
	 * 
	 * @return string
	 */
	protected function shortList( $start, $end, array $diWikiPages ) {
		global $wgContLang;

		$startDv = SMWDataValueFactory::newDataItemValue( $diWikiPages[$start], $this->mProperty );
		$sortkey = smwfGetStore()->getWikiPageSortKey( $diWikiPages[$start] );
		$start_char = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );
		$r = '<h3>' . htmlspecialchars( $start_char ) . "</h3>\n" .
		     '<ul><li>' . $startDv->getLongHTMLText( $this->getSkin() ) . '</li>';

		$prev_start_char = $start_char;
		for ( $index = $start + 1; $index < $end; $index++ ) {
			$dataValue = SMWDataValueFactory::newDataItemValue( $diWikiPages[$index], $this->mProperty );
			$sortkey = smwfGetStore()->getWikiPageSortKey( $diWikiPages[$index] );
			$start_char = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );

			if ( $start_char != $prev_start_char ) {
				$r .= "</ul><h3>" . htmlspecialchars( $start_char ) . "</h3>\n<ul>";
				$prev_start_char = $start_char;
			}

			$r .= '<li>' . $dataValue->getLongHTMLText( $this->getSkin() ) . '</li>';
		}

		$r .= '</ul>';

		return $r;
	}

}