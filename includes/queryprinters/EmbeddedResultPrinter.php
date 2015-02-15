<?php

namespace SMW;

use SMWQueryResult;

use Title;

/**
 * Printer for embedded data.
 *
 * Embeds in the page output the contents of the pages in the query result set.
 * Printouts are ignored: it only matters which pages were returned by the query.
 * The optional "titlestyle" formatting parameter can be used to apply a format to
 * the headings for the page titles. If "titlestyle" is not specified, a <h1> tag is
 * used.
 *
 * @license GNU GPL v2+
 * @since 1.7
 *
 * @author Fernando Correia
 * @author Markus Krötzsch
 */
class EmbeddedResultPrinter extends ResultPrinter {

	protected $m_showhead;
	protected $m_embedformat;

	/**
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.7
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->m_showhead = !$params['embedonly'];
		$this->m_embedformat = $params['embedformat'];
	}

	public function getName() {
		return wfMessage( 'smw_printername_embedded' )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputMode ) {
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
		$footer = '';
		$embstart = '';
		$embend = '';
		$headstart = '';
		$headend = '';
		$this->hasTemplates = true;

		switch ( $this->m_embedformat ) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$headstart = '<' . $this->m_embedformat . '>';
				$headend = '</' . $this->m_embedformat . ">\n";
			break;
			case 'ul': case 'ol':
				$result .= '<' . $this->m_embedformat . '>';
				$footer = '</' . $this->m_embedformat . '>';
				$embstart = '<li>';
				$headend = "<br />\n";
				$embend = "</li>\n";
			break;
		}

		// Print all result rows:
		foreach ( $res->getResults() as $diWikiPage ) {
			if ( $diWikiPage instanceof DIWikiPage  ) { // ensure that we deal with title-likes
				$dvWikiPage = DataValueFactory::getInstance()->newDataItemValue( $diWikiPage, null );
				$result .= $embstart;

				if ( $this->m_showhead ) {
					$result .= $headstart . $dvWikiPage->getLongWikiText( $this->mLinker ) . $headend;
				}

				if ( $dvWikiPage->getLongWikiText() != $title ) {
					if ( $diWikiPage->getNamespace() == NS_MAIN ) {
						$result .= '{{:' . $diWikiPage->getDBkey() . '}}';
					} else {
						$result .= '{{' . $dvWikiPage->getLongWikiText() . '}}';
					}
				} else { // block recursion
					$result .= '<b>' . $dvWikiPage->getLongWikiText() . '</b>';
				}

				$result .= $embend;
			}
		}

		// show link to more results
		if ( $this->linkFurtherResults( $res ) ) {
			$result .= $embstart
				. $this->getFurtherResultsLink( $res, $outputMode )->getText( SMW_OUTPUT_WIKI, $this->mLinker )
				. $embend;
		}

		$result .= $footer;

		return $result;
	}

	public function getParameters() {
		$params = parent::getParameters();

		$params[] = array(
			'name' => 'embedformat',
			'message' => 'smw-paramdesc-embedformat',
			'default' => 'h1',
			'values' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ol', 'ul' ),
		);

		$params[] = array(
			'name' => 'embedonly',
			'type' => 'boolean',
			'message' => 'smw-paramdesc-embedonly',
			'default' => false,
		);

		return $params;
	}

}
