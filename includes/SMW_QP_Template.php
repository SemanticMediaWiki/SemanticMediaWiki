<?php
/**
 * Print query results using templates.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer for template data. Passes a result row as anonymous parameters to
 * a given template (which might ignore them or not) and prints the result.
 *
 * @ingroup SMWQuery
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

	public function getName() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return wfMsg('smw_printername_template');
	}

	protected function getResultText($res, $outputmode) {
		// print all result rows
		if ($this->m_template == false) {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$res->addErrors(array(wfMsgForContent('smw_notemplategiven')));
			return '';
		}
		$this->hasTemplates = true;

		$result = '';
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
			$result .= '{{' . $this->m_template .  $wikitext . '}}';
		}

		// show link to more results
		if ( $this->linkFurtherResults($res) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel($outputmode)) {
				$link->setCaption($this->getSearchLabel($outputmode));
			}
			$link->setParameter('template','format');
			$link->setParameter($this->m_template,'template');
			if (array_key_exists('link', $this->m_params)) { // linking may interfere with templates
				$link->setParameter($this->m_params['link'],'link');
			}
			$result .= $link->getText(SMW_OUTPUT_WIKI,$this->mLinker);
		}
		return $result;
	}
}
