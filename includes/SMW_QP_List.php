<?php
/**
 * Print query results in lists.
 * @author Markus Krötzsch
 */

/**
 * New implementation of SMW's printer for results in lists.
 *
 * Somewhat confusing code, since one has to iterate through lists, inserting texts 
 * in between their elements depending on whether the element is the first that is 
 * printed, the first that is printed in parentheses, or the last that will be printed.
 * Maybe one could further simplify this.
 *
 * @note AUTOLOADED
 */
class SMWListResultPrinter extends SMWResultPrinter {

	protected $mSep = '';
	protected $mTemplate = '';

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('sep', $params)) {
			$this->mSep = str_replace('_',' ',$params['sep']);
			if ($outputmode==SMW_OUTPUT_HTML) {
				$this->mSep = htmlspecialchars($this->mSep);
			}
		}
		if (array_key_exists('template', $params)) {
			$this->mTemplate = trim($params['template']);
		}
	}

	protected function getResultText($res,$outputmode) {
		global $wgTitle,$smwgStoreActive;
		// print header
		$result = $this->mIntro;
		if ( ('ul' == $this->mFormat) || ('ol' == $this->mFormat) ) {
			$result .= '<' . $this->mFormat . '>';
			$footer = '</' . $this->mFormat . '>';
			$rowstart = "\n\t<li>";
			$rowend = '</li>';
			$plainlist = false;
		} else {
			if ($this->mSep != '') {
				$listsep = $this->mSep;
				$finallistsep = $listsep;
			} else {  // default list ", , , and, "
				$listsep = ', ';
				$finallistsep = wfMsgForContent('smw_finallistconjunct') . ' ';
			}
			$footer = '';
			$rowstart = '';
			$rowend = '';
			$plainlist = true;
		}

		if ($this->mTemplate != '') {
			global $wgParser;
			$parser_options = new ParserOptions();
			$parser_options->setEditSection(false);  // embedded sections should not have edit links
			$parser = clone $wgParser;
			$usetemplate = true;
		} else {
			$usetemplate = false;
		}

		// print all result rows
		$first_row = true;
		$row = $res->getNext();
		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead
			if ( !$first_row && $plainlist )  {
				if ($nextrow !== false) $result .= $listsep; // the comma between "rows" other than the last one
				else $result .= $finallistsep;
			} else $result .= $rowstart;

			$first_col = true;
			if ($usetemplate) { // build template code
				$wikitext = '';
				foreach ($row as $field) {
					$wikitext .= "|";
					$first_value = true;
					while ( ($text = $field->getNextText(SMW_OUTPUT_WIKI, $this->getLinker($first_col))) !== false ) {
						if ($first_value) $first_value = false; else $wikitext .= ', ';
						$wikitext .= $text;
					}
					$first_col = false;
				}
				$result .= '[[SMW::off]]{{' . $this->mTemplate . $wikitext . '}}[[SMW::on]]';
				//str_replace(array('=','|'), array('&#x003D;', '&#x007C;'), // encode '=' and '|' for use in templates (templates fail otherwise) -- this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
			} else {  // build simple list
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?
				foreach ($row as $field) {
					$first_value = true;
					while ( ($text = $field->getNextText($outputmode, $this->getLinker($first_col))) !== false ) {
						if (!$first_col && !$found_values) { // first values after first column
							$result .= ' (';
							$found_values = true;
						} elseif ($found_values || !$first_value) {
						// any value after '(' or non-first values on first column
							$result .= ', ';
						}
						if ($first_value) { // first value in any column, print header
							$first_value = false;
							if ( $this->mShowHeaders && ('' != $field->getPrintRequest()->getLabel()) ) {
								$result .= $field->getPrintRequest()->getText($outputmode, $this->mLinker) . ' ';
							}
						}
						$result .= $text; // actual output value
					}
					$first_col = false;
				}
				if ($found_values) $result .= ')';
			}
			$result .= $rowend;
			$first_row = false;
			$row = $nextrow;
		}

		if ($usetemplate) {
			$old_smwgStoreActive = $smwgStoreActive;
			$smwgStoreActive = false; // no annotations stored, no factbox printed
			if ($outputmode === SMW_OUTPUT_HTML) {
				$parserOutput = $parser->parse($result, $wgTitle, $parser_options);
				$result = $parserOutput->getText();
			} else {
				$result = $parser->preprocess($result, $wgTitle, $parser_options);
			}
			$smwgStoreActive = $old_smwgStoreActive;
		}

		if ( $this->mInline && $res->hasFurtherResults() ) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply defaults
				if ('ol' == $this->mFormat) $label = '';
				else $label = wfMsgForContent('smw_iq_moreresults');
			}
			if (!$first_row) $result .= ' '; // relevant for list, unproblematic for ul/ol
			if ($label != '') {
				$result .= $rowstart . $this->getFurtherResultsLink($outputmode,$res,$label) . $rowend;
			}
		}

		// print footer
		$result .= $footer;
		return $result;
	}

}
