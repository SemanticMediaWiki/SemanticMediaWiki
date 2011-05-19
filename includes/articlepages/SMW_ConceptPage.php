<?php
/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * Concept: pages. Very simliar to CategoryPage.
 * @ingroup SMW
 */
class SMWConceptPage extends SMWOrderedListPage {
	protected $m_errors;

	/**
	 * Use higher limit. This operation is very similar to showing members of categories.
	 */
	protected function initParameters() {
		global $smwgConceptPagingLimit;
		$this->limit = $smwgConceptPagingLimit;
		return true;
	}

	/**
	 * Fill the internal arrays with the list of wiki page data items to be
	 * displayed (possibly plus one additional article that indicates
	 * further results).
	 */
	protected function doQuery() {
		if ( $this->limit > 0 ) {
			$store = smwfGetStore();
			$thisDiWikiPage = SMWDIWikiPage::newFromTitle( $this->mTitle );
			$desc = new SMWConceptDescription( $thisDiWikiPage );
			
			if ( $this->from != '' ) {
				$diWikiPage = new SMWDIWikiPage( $this->from, NS_MAIN, '' ); // make a dummy wiki page as boundary
				$fromdesc = new SMWValueDescription( $diWikiPage, null, SMW_CMP_GEQ );
				$desc = new SMWConjunction( array( $desc, $fromdesc ) );
				$order = 'ASC';
			} elseif ( $this->until != '' ) {
				$diWikiPage = new SMWDIWikiPage( $this->until, NS_MAIN, '' ); // make a dummy wiki page as boundary
				$fromdesc = new SMWValueDescription( $diWikiPage, null, SMW_CMP_LEQ );
				$neqdesc = new SMWValueDescription( $diWikiPage, null, SMW_CMP_NEQ ); // do not include boundary in this case
				$desc = new SMWConjunction( array( $desc, $fromdesc, $neqdesc ) );
				$order = 'DESC';
			} else {
				$order = 'ASC';
			}
			
			$desc->addPrintRequest( new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, '' ) );
			$query = new SMWQuery( $desc );
			$query->sortkeys[''] = $order;
			$query->setLimit( $this->limit + 1 );

			$result = $store->getQueryResult( $query );
			$row = $result->getNext();
			
			while ( $row !== false ) {
				$this->diWikiPages[] = end( $row )->getNextDataItem();
				$row = $result->getNext();
			}
			
			if ( $order == 'DESC' ) {
				$this->diWikiPages = array_reverse( $this->diWikiPages );
			}
			
			$this->m_errors = $query->getErrors();
		} else {
			$this->diWikiPages = array();
			$this->errors = array();
		}
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which
	 * shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$r = '';
		$ti = htmlspecialchars( $this->mTitle->getText() );
		$nav = $this->getNavigationLinks();
		$r .= '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n";

		$r .= '<h2>' . wfMsg( 'smw_concept_header', $ti ) . "</h2>\n";
		$r .= wfMsgExt( 'smw_conceptarticlecount', array( 'parsemag' ), min( $this->limit, count( $this->diWikiPages ) ) ) . smwfEncodeMessages( $this->m_errors ) .  "\n";

		$r .= $this->formatList();
		$r .= "\n</div>" . $nav;
		wfProfileOut( __METHOD__ . ' (SMW)' );
		return $r;
	}

	/**
	 * Format a list of wiki pages chunked by letter, either as a bullet
	 * list or a columnar format, depending on the length.
	 *
	 * @param int $cutoff
	 * @return string
	 */
	private function formatList( $cutoff = 6 ) {
		$end = count( $this->diWikiPages );
		
		if ( $end > $this->limit ) {
			if ( $this->until != '' ) {
				$start = 1;
			} else {
				$start = 0;
				$end --;
			}
		} else {
			$start = 0;
		}

		if ( count ( $this->diWikiPages ) > $cutoff ) {
			return $this->columnList( $start, $end, $this->diWikiPages );
		} elseif ( count( $this->diWikiPages ) > 0 ) {
			return $this->shortList( $start, $end, $this->diWikiPages );
		} else {
			return '';
		}
	}

}

