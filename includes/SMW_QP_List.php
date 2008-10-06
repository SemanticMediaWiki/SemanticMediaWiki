<?php
/**
 * Print query results in lists.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for results in lists.
 *
 * Somewhat confusing code, since one has to iterate through lists, inserting texts 
 * in between their elements depending on whether the element is the first that is 
 * printed, the first that is printed in parentheses, or the last that will be printed.
 * Maybe one could further simplify this.
 *
 * @ingroup SMWQuery
 */
class SMWListResultPrinter extends SMWResultPrinter {

	protected $mSep = '';
	protected $mTemplate = '';
	protected $mUserParam = '';

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('sep', $params)) {
			$this->mSep = str_replace('_',' ',$params['sep']);
		}
		if (array_key_exists('template', $params)) {
			$this->mTemplate = trim($params['template']);
		}
		if (array_key_exists('userparam', $params)) {
			$this->mUserParam = trim($params['userparam']);
		}
	}


	protected function getResultText($res,$outputmode) {
		// Determine mark-up strings used around list items:
		if ( ('ul' == $this->mFormat) || ('ol' == $this->mFormat) ) {
			$header = '<' . $this->mFormat . '>';
			$footer = '</' . $this->mFormat . '>';
			$rowstart = "\n\t<li>";
			$rowend = '</li>';
			$plainlist = false;
		} else {
			if ($this->mSep != '') {
				$listsep = $this->mSep;
				$finallistsep = $listsep;
			} else {  // default list ", , , and "
				wfLoadExtensionMessages('SemanticMediaWiki');
				$listsep = ', ';
				$finallistsep = wfMsgForContent('smw_finallistconjunct') . ' ';
			}
			$header = '';
			$footer = '';
			$rowstart = '';
			$rowend = '';
			$plainlist = true;
		}

		// Print header:
		$result = $header;

		// Print all result rows:
		$first_row = true;
		$row = $res->getNext();
		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead
			if ( !$first_row && $plainlist )  {
				$result .=  ($nextrow !== false)?$listsep:$finallistsep; // the comma between "rows" other than the last one
			} else {
				$result .= $rowstart;
			}

			$first_col = true;
			if ($this->mTemplate != '') { // build template code
				$this->hasTemplates = true;
				$wikitext = ($this->mUserParam)?"|userparam=$this->mUserParam":'';
				$i = 1; // explicitly number parameters for more robust parsing (values may contain "=")
				foreach ($row as $field) {
					$wikitext .= '|' . $i++ . '=';
					$first_value = true;
					while ( ($text = $field->getNextText(SMW_OUTPUT_WIKI, $this->getLinker($first_col))) !== false ) {
						if ($first_value) $first_value = false; else $wikitext .= ', ';
						$wikitext .= $text;
					}
					$first_col = false;
				}
				$result .= '{{' . $this->mTemplate . $wikitext . '}}';
				//str_replace('|', '&#x007C;', // encode '|' for use in templates (templates fail otherwise) -- this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
			} else {  // build simple list
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?
				foreach ($row as $field) {
					$first_value = true;
					while ( ($text = $field->getNextText(SMW_OUTPUT_WIKI, $this->getLinker($first_col))) !== false ) {
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
								$result .= $field->getPrintRequest()->getText(SMW_OUTPUT_WIKI, $this->mLinker) . ' ';
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

		// Make label for finding further results
		if ( $this->linkFurtherResults($res) && ( ('ol' != $this->mFormat) || ($this->getSearchLabel(SMW_OUTPUT_WIKI)) ) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel(SMW_OUTPUT_WIKI)) {
				$link->setCaption($this->getSearchLabel(SMW_OUTPUT_WIKI));
			}
			/// NOTE: passing the parameter sep is not needed, since we use format=ul

			$link->setParameter('ul','format'); // always use ul, other formats hardly work as search page output
			if ($this->mTemplate != '') {
				$link->setParameter($this->mTemplate,'template');
				if (array_key_exists('link', $this->m_params)) { // linking may interfere with templates
					$link->setParameter($this->m_params['link'],'link');
				}
			}
			$result .= $rowstart . $link->getText(SMW_OUTPUT_WIKI,$this->mLinker) . $rowend;
		}

		// Print footer:
		$result .= $footer;
		return $result;
	}

}
