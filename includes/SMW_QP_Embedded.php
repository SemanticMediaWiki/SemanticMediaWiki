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
 * Only the first column of the query is considered. If it is a page reference then that page's contents is embedded.
 * The optional "titlestyle" formatting parameter can be used to apply a format to the headings for the page titles.
 * If "titlestyle" is not specified, a <h1> tag is used.
 * @author Fernando Correia
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWEmbeddedResultPrinter extends SMWResultPrinter {

	protected $m_showhead;
	protected $m_embedformat;

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('embedonly', $params)) {
			$this->m_showhead = false;
		} else {
			$this->m_showhead = true;
		}
		if (array_key_exists('embedformat', $params)) {
			$this->m_embedformat = trim($params['embedformat']);
		} else {
			$this->m_embedformat = 'h1';
		}
	}

	protected function getResultText($res,$outputmode) {
		global $smwgEmbeddingList, $wgParser;
		$title = $wgParser->getTitle();
		if ($title === NULL) { // try that in emergency, needed in 1.11 in Special:Ask
			global $wgTitle;
			$title = $wgTitle;
		}

		if (!isset($smwgEmbeddingList)) { // used to catch recursions, sometimes more restrictive than needed, but no major use cases should be affected by that!
			$smwgEmbeddingList = array($title->getPrefixedText());
			$oldEmbeddingList = array($title->getPrefixedText());
		} else {
			$oldEmbeddingList = array_values($smwgEmbeddingList);
		}

		// print header
		$result = '';
		$this->hasTemplates = true;

		switch ($this->m_embedformat) {
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
		while (  $row = $res->getNext() ) {
			$first_col = true;
			foreach ($row as $field) {
				if ( $field->getPrintRequest()->getTypeID() == '_wpg' ) { // ensure that we deal with title-likes
					while ( ($object = $field->getNextObject()) !== false ) {
						$result .= $embstart;
						$text= $object->getLongText(SMW_OUTPUT_WIKI,$this->getLinker(true));
						if ($this->m_showhead) {
							$result .= $headstart . $text . $headend;
						}
						if (!in_array($object->getLongWikiText(), $smwgEmbeddingList)) { // prevent recursion!
							$smwgEmbeddingList[] = $object->getLongWikiText();
							if ($object->getNamespace() == NS_MAIN) {
								$articlename = ':' . $object->getDBkey();
							} else {
								$articlename = $object->getLongWikiText();
							}
							$result .= '{{' . $articlename . '}}';
						} else {
							$result .= '<b>' . $object->getLongWikiText() . '</b>';
						}
						$result .= $embend;
					}
				}
				break;  // only use first column for now
			}
		}

		// show link to more results
		if ( $this->linkFurtherResults($res) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel(SMW_OUTPUT_WIKI)) {
				$link->setCaption($this->getSearchLabel(SMW_OUTPUT_WIKI));
			}
			$link->setParameter('embedded','format');
			$format = $this->m_embedformat;
			if ($format=='ol') $format = 'ul'; // ordered lists confusing in paged output
			$link->setParameter($format,'embedformat');
			if (!$this->m_showhead) {
				$link->setParameter('1','embedonly');
			}
			$result .= $embstart . $link->getText(SMW_OUTPUT_WIKI,$this->mLinker) . $embend;
		}
		$result .= $footer;

		$smwgEmbeddingList = array_values($oldEmbeddingList);
		return $result;
	}
}
