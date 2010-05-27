<?php
/**
 * Print query results by embeddings them into pages.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer for embedded data.
 * Embeds in the page output the contents of the pages in the query result set.
 * Printouts are ignored: it only matters which pages were returned by the query.
 * The optional "titlestyle" formatting parameter can be used to apply a format to
 * the headings for the page titles. If "titlestyle" is not specified, a <h1> tag is
 * used.
 * @author Fernando Correia
 * @author Markus Krötzsch
 * @ingroup SMWQuery
 */
class SMWEmbeddedResultPrinter extends SMWResultPrinter {

	protected $m_showhead;
	protected $m_embedformat;

	protected function readParameters( $params, $outputmode ) {
		SMWResultPrinter::readParameters( $params, $outputmode );

		if ( array_key_exists( 'embedonly', $params ) ) {
			$this->m_showhead = false;
		} else {
			$this->m_showhead = true;
		}
		if ( array_key_exists( 'embedformat', $params ) ) {
			$this->m_embedformat = trim( $params['embedformat'] );
		} else {
			$this->m_embedformat = 'h1';
		}
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_embedded' );
	}

	protected function getResultText( $res, $outputmode ) {
		global $wgParser;
		// No page should embed itself, find out who we are:
		if ( $wgParser->getTitle() instanceof Title ) {
			$title = $wgParser->getTitle()->getPrefixedText();
		} else { // this is likely to be in vain -- this case is typical if we run on special pages
			global $wgTitle;
			$title = $wgTitle->getPrefixedText();
		}

		// print header
		$result = '';
		$this->hasTemplates = true;

		switch ( $this->m_embedformat ) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$footer = '';
				$embstart = '';
				$headstart = '<' . $this->m_embedformat . '>';
				$headend = '</' . $this->m_embedformat . ">\n";
				$embend = '';
			break;
			case 'ul': case 'ol':
				$result .= '<' . $this->m_embedformat . '>';
				$footer = '</' . $this->m_embedformat . '>';
				$embstart = '<li>';
				$headstart = '';
				$headend = "<br />\n";
				$embend = "</li>\n";
			break;
		}

		// Print all result rows:
		foreach ( $res->getResults() as $page ) {
			if ( $page->getTypeID() == '_wpg' ) { // ensure that we deal with title-likes
				$result .= $embstart;
				if ( $this->m_showhead ) {
					$result .= $headstart . $page->getLongWikiText( $this->mLinker ) . $headend;
				}
				if ( $page->getLongWikiText() != $title ) {
					$result .= '{{' . ( ( $page->getNamespace() == NS_MAIN ) ?
					            ':' . $page->getDBkey():$page->getLongWikiText() ) .
								'}}';
				} else {
					$result .= '<b>' . $page->getLongWikiText() . '</b>';
				}
				$result .= $embend;
			}
		}

		// show link to more results
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $res->getQueryLink();
			if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) {
				$link->setCaption( $this->getSearchLabel( SMW_OUTPUT_WIKI ) );
			}
			$link->setParameter( 'embedded', 'format' );
			// ordered lists confusing in paged output
			$format = ( $this->m_embedformat == 'ol' ) ? 'ul':$this->m_embedformat;
			$link->setParameter( $format, 'embedformat' );
			if ( !$this->m_showhead ) {
				$link->setParameter( '1', 'embedonly' );
			}
			$result .= $embstart . $link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . $embend;
		}
		$result .= $footer;

		return $result;
	}

        public function getParameters() {
                $params = parent::getParameters();
                $params[] = array( 'name' => 'embedformat', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_embedformat' ) );
                $params[] = array( 'name' => 'embedonly', 'type' => 'boolean', 'description' => wfMsg( 'smw_paramdesc_embedonly' ) );
                return $params;
        }

}
