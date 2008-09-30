<?php
/**
 * The class in this file provides means of rendering a "Factbox" in articles.
 * @file
 * @ingroup SMW
 * @author Markus Krötzsch
 */

/**
 * Static class for printing semantic data in a "Factbox".
 * @ingroup SMW
 * @bug The Factbox should not be printed in a parser hook but by the skin. This would also make some hacky fields such as m_printed and m_blocked obsolete.
 */
class SMWFactbox {

	/**
	 * True if the respective article is newly created. This affects some
	 * storage operations.
	 */
	static protected $m_new = false;
	/**
	 * True if Factbox was printed, our best attempt at reliably preventing multiple
	 * Factboxes to appear on one page.
	 */
	static protected $m_printed = false;
	/**
	 * True if the next try on printing should be blocked
	 */
	static protected $m_blocked = false;

	/**
	 * Blocks the next rendering of the Factbox
	 */
	static function blockOnce() {
		SMWFactbox::$m_blocked = true;
	}

	/**
	 * True if the respective article is newly created, but always false until
	 * an article is actually saved.
	 */
	static function isNewArticle() {
		return SMWFactbox::$m_new;
	}

//// Methods for adding data to the object

	/**
	 * Called to state that the respective article was newly created. Not known until
	 * an article is actually saved.
	 */
	static function setNewArticle() {
		SMWFactbox::$m_new = true;
	}

//// Methods for printing the content of this object into an factbox   */

	/**
	 * This method prints semantic data at the bottom of an article.
	 */
	static function printFactbox(&$text, $parser) {
		global $wgContLang, $wgServer, $smwgShowFactbox, $smwgShowFactboxEdit, $smwgIP, $wgRequest;
		if (SMWFactbox::$m_blocked) { SMWFactbox::$m_blocked = false; return;}		
		if (SMWFactbox::$m_printed) return;
		wfProfileIn("SMWFactbox::printFactbox (SMW)");

		// Global settings:
		if ( $wgRequest->getCheck('wpPreview') ) {
			$showfactbox = $smwgShowFactboxEdit;
		} else {
			$showfactbox = $smwgShowFactbox;
		}
		// Page settings via Magic Words:
		$mw = MagicWord::get('SMW_NOFACTBOX');
		if ($mw->matchAndRemove($text)) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		}
		$mw = MagicWord::get('SMW_SHOWFACTBOX');
		if ($mw->matchAndRemove($text)) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		}

		switch ($showfactbox) {
		case SMW_FACTBOX_HIDDEN: // never
			wfProfileOut("SMWFactbox::printFactbox (SMW)");
			SMWFactbox::$m_printed = true; // do not print again, period (the other cases may safely try again, if new data should come in)
			return;
		case SMW_FACTBOX_SPECIAL: // only when there are special properties
			if ( (SMWParseData::getSMWData($parser) === NULL) || (!SMWParseData::getSMWData($parser)->hasVisibleSpecialProperties()) ) {
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
				return;
			}
			break;
		case SMW_FACTBOX_NONEMPTY: // only when non-empty
			if ( (SMWParseData::getSMWData($parser) === NULL) || (!SMWParseData::getSMWData($parser)->hasProperties()) && (!SMWParseData::getSMWData($parser)->hasVisibleSpecialProperties()) ) {
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
				return;
			}
			break;
		case SMW_FACTBOX_SHOWN: // escape only if we have no data container at all 
			///NOTE: this should not happen, but we have no way of being fully sure, hence be prepared
			if (SMWParseData::getSMWData($parser) === NULL) {
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
				return;
			}
			break;
		}
		SMWFactbox::$m_printed = true;

		smwfRequireHeadItem(SMW_HEADER_STYLE);
		$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . SMWParseData::getSMWData($parser)->getSubject()->getWikiValue(), 'rdflink');

		$browselink = SMWInfolink::newBrowsingLink(SMWParseData::getSMWData($parser)->getSubject()->getText(), SMWParseData::getSMWData($parser)->getSubject()->getWikiValue(), 'swmfactboxheadbrowse');
		// The "\n" is to ensure that lists on the end of articles are terminated
		// before the div starts. It would of course be much cleaner to print the
		// factbox in another way, similar to the way that categories are printed
		// now. However, this would require more patching of MediaWiki code ...
		$text .= "\n" . '<div class="smwfact">' .
		         '<span class="smwfactboxhead">' . wfMsgForContent('smw_factbox_head', $browselink->getWikiText() ) . '</span>' .
		         '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
		         '<table class="smwfacttable">' . "\n";
		SMWFactbox::printProperties($text, $parser);
		$text .= '</table></div>';
		wfProfileOut("SMWFactbox::printFactbox (SMW)");
	}

	/**
	 * This method prints (special) property values at the bottom of an article.
	 */
	static protected function printProperties(&$text, $parser) {
		if (!SMWParseData::getSMWData($parser)->hasProperties() && !SMWParseData::getSMWData($parser)->hasSpecialProperties()) {
			return;
		}
		global $wgContLang;
		
		wfLoadExtensionMessages('SemanticMediaWiki');

		foreach(SMWParseData::getSMWData($parser)->getProperties() as $key => $property) {
			if ($property instanceof Title) {
				$text .= '<tr><td class="smwpropname">[[' . $property->getPrefixedText() . '|' . preg_replace('/[ ]/u','&nbsp;',$property->getText(),2) . ']] </td><td class="smwprops">';
				// TODO: the preg_replace is a kind of hack to ensure that the left column does not get too narrow; maybe we can find something nicer later
			} else { // special property
				if ($key{0} == '_') continue; // internal special property without label
				smwfRequireHeadItem(SMW_HEADER_TOOLTIP);
				$text .= '<tr><td class="smwspecname"><span class="smwttinline"><span class="smwbuiltin">[[' .
				          $wgContLang->getNsText(SMW_NS_PROPERTY) . ':' . $key . '|' . $key .
				          ']]</span><span class="smwttcontent">' . wfMsgForContent('smw_isspecprop') .
				          '</span></span></td><td class="smwspecs">';
			}

			$propvalues = SMWParseData::getSMWData($parser)->getPropertyValues($property);
			$l = count($propvalues);
			$i=0;
			foreach ($propvalues as $propvalue) {
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else {
						$text .= ', ';
					}
				}
				$i+=1;
				$text .= $propvalue->getLongWikiText(true) . $propvalue->getInfolinkText(SMW_OUTPUT_WIKI);
			}
			$text .= '</td></tr>';
		}
	}

}
