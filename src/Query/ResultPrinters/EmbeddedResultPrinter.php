<?php

namespace SMW\Query\ResultPrinters;

use SMWQueryResult as QueryResult;
use Title;
use SMW\DataValueFactory;
use SMW\DIWikiPage;

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

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return wfMessage( 'smw_printername_embedded' )->text();
	}

	/**
	 * @see ResultPrinter::isDeferrable
	 *
	 * {@inheritDoc}
	 */
	public function isDeferrable() {
		return true;
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			'name' => 'embedformat',
			'message' => 'smw-paramdesc-embedformat',
			'default' => 'h1',
			'values' => [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ol', 'ul' ],
		];

		$definitions[] = [
			'name' => 'embedonly',
			'type' => 'boolean',
			'message' => 'smw-paramdesc-embedonly',
			'default' => false,
		];

		return $definitions;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {

		/**
		 * @see ResultPrinter::transcludeAnnotation
		 *
		 * Ensure that there is an annotation block in place before starting the
		 * parse and transclution process. Unfortunately we are unable to block
		 * the inclusion of categories which are attached to a MediaWiki
		 * object we have no immediate access or control.
		 */
		$this->transcludeAnnotation = false;

		/**
		 * @see ResultPrinter::hasTemplates
		 */
		$this->hasTemplates = true;

		return $this->buildText( $queryResult, $outputMode );
	}

	private function buildText( $queryResult, $outputMode ) {

		// REMOVE the parser reference
		// Use $queryResult->getQuery()->getContextPage()
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

		switch ( $this->params['embedformat'] ) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$headstart = '<' . $this->params['embedformat'] . '>';
				$headend = '</' . $this->params['embedformat'] . ">\n";
			break;
			case 'ul': case 'ol':
				$result .= '<' . $this->params['embedformat'] . '>';
				$footer = '</' . $this->params['embedformat'] . '>';
				$embstart = '<li>';
				$headend = "<br />\n";
				$embend = "</li>\n";
			break;
		}

		$dataValueFactory = DataValueFactory::getInstance();

		// Print all result rows:
		foreach ( $queryResult->getResults() as $diWikiPage ) {
			if ( $diWikiPage instanceof DIWikiPage  ) { // ensure that we deal with title-likes
				$dvWikiPage = $dataValueFactory->newDataValueByItem( $diWikiPage, null );
				$result .= $embstart;

				if ( !$this->params['embedonly'] ) {
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
		if ( $this->linkFurtherResults( $queryResult ) ) {
			$link = $this->getFurtherResultsLink( $queryResult, $outputMode );
			$result .= $embstart . $link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . $embend;
		}

		$result .= $footer;

		return $result;
	}

}
