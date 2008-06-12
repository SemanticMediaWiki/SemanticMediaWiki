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
	protected $m_userparam;

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('template', $params)) {
			$this->m_template = trim($params['template']);
		} else {
			$this->m_template = false;
		}

		if (array_key_exists('userparam', $params)) {
			$this->m_userparam = trim($params['userparam']);
		} else {
			$this->m_userparam = false;
		}
	}

	protected function getResultText($res, $outputmode) {
		// handle factbox
		global $smwgStoreActive, $wgParser;
		$parsetitle = $wgParser->getTitle();
		if ($parsetitle === NULL) { // try that in emergency, needed in 1.11 in Special:Ask
			global $wgTitle;
			$parsetitle = $wgTitle;
		}

		// print all result rows
		if ($this->m_template == false) {
			$res->addErrors(array(wfMsgForContent('smw_notemplategiven')));
			return '';
		}

		$parserinput = $this->mIntro;
		while ( $row = $res->getNext() ) {
			$i = 1; // explicitly number parameters for more robust parsing (values may contain "=")
			$wikitext = ($this->m_userparam)?"|userparam=$this->m_userparam":'';
			$firstcol = true;
			foreach ($row as $field) {
				$wikitext .= '|' . $i++ . '=';
				$first = true;
				while ( ($text = $field->getNextText(SMW_OUTPUT_WIKI, $this->getLinker($firstcol))) !== false ) {
					if ($first) {
						$first = false;
					} else {
						$wikitext .= ', ';
					}
					$wikitext .= $text; //str_replace('|', '&#x007C;',$text); // encode '|' for use in templates (templates fail otherwise) -- this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
				}
				$firstcol = false;
			}
			$parserinput .= '[[SMW::off]]{{' . $this->m_template .  $wikitext . '}}[[SMW::on]]';
		}

		$old_smwgStoreActive = $smwgStoreActive;
		$smwgStoreActive = false; // no annotations stored, no factbox printed
		$parser_options = new ParserOptions();
		$parser_options->setEditSection(false);  // embedded sections should not have edit links
		$parser = clone $wgParser;
		if ($outputmode == SMW_OUTPUT_WIKI) {
			if ( method_exists($parser, 'getPreprocessor') ) {
				$frame = $parser->getPreprocessor()->newFrame();
				$dom = $parser->preprocessToDom( $parserinput );
				$result = $frame->expand( $dom );
			} else {
				$result = $parser->preprocess($parserinput, $parsetitle, $parser_options);
			}
		} else /* SMW_OUTPUT_HTML, SMW_OUTPUT_FILE */ {
			$parserOutput = $parser->parse($parserinput, $parsetitle, $parser_options);
			$result = $parserOutput->getText();
		}
		$smwgStoreActive = $old_smwgStoreActive;
		// show link to more results
		if ( $this->mInline && $res->hasFurtherResults() && ($this->mSearchlabel !== '') ) {
			$link = $res->getQueryLink();
			if ($this->mSearchlabel) {
				$link->setCaption($this->mSearchlabel);
			}
			$link->setParameter('template','format');
			$link->setParameter($this->m_template,'template');
			if (array_key_exists('link', $this->m_params)) { // linking may interfere with templates
				$link->setParameter($this->m_params['link'],'link');
			}
			$result .= $link->getText($outputmode,$this->mLinker);
		}
		return $result;
	}
}
