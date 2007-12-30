<?php
/**
 * Print query results using templates.
 * @author Markus Krötzsch
 */

/**
 * Printer for template data. Passes a result row as anonymous parameters to
 * a given template (which might ignore them or not) and prints the result.
 *
 * @note AUTOLOADED
 */
class SMWTemplateResultPrinter extends SMWResultPrinter {

	protected $m_template;

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('template', $params)) {
			$this->m_template = trim($params['template']);
		} else {
			$this->m_template = false;
		}
	}

	protected function getResultText($res, $outputmode) {
		// handle factbox
		global $smwgStoreActive, $wgTitle;

		// print all result rows
		if ($this->m_template == false) {
			return 'Please provide parameter "template" for query to work.'; // TODO: internationalise, beautify
		}

		$parserinput = $this->mIntro;
		while ( $row = $res->getNext() ) {
			$wikitext = '';
			$firstcol = true;
			foreach ($row as $field) {
				$wikitext .= "|";
				$first = true;
				while ( ($text = $field->getNextText(SMW_OUTPUT_WIKI, $this->getLinker($firstcol))) !== false ) {
					if ($first) {
						$first = false; 
					} else {
						$wikitext .= ', ';
					}
					$wikitext .= $text; //str_replace(array('=','|'), array('&#x003D;', '&#x007C;'),$text); // encode '=' and '|' for use in templates (templates fail otherwise)
				}
				$firstcol = false;
			}
			$parserinput .= '[[SMW::off]]{{' . $this->m_template .  $wikitext . '}}[[SMW::on]]';
		}

		$old_smwgStoreActive = $smwgStoreActive;
		$smwgStoreActive = false; // no annotations stored, no factbox printed
		$parser_options = new ParserOptions();
		$parser_options->setEditSection(false);  // embedded sections should not have edit links
		$parser = new Parser();
		if ($outputmode == SMW_OUTPUT_HTML) {
			$parserOutput = $parser->parse($parserinput, $wgTitle, $parser_options);
			$result = $parserOutput->getText();
		} else {
			$result = $parser->preprocess($parserinput, $wgTitle, $parser_options);
		}
		$smwgStoreActive = $old_smwgStoreActive;
		// show link to more results
		if ( $this->mInline && $res->hasFurtherResults() ) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply defaults
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= $this->getFurtherResultsLink($outputmode,$res,$label);
			}
		}
		return $result;
	}
}
