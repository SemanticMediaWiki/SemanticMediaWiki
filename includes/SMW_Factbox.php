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
 */
class SMWFactbox {


	/**
	 * This function creates wiki text suitable for rendering a Factbox for a given
	 * SMWSemanticData object that holds all relevant data. It also checks whether the
	 * given setting of $showfactbox requires displaying the given data at all.
	 */
	static public function getFactboxText(SMWSemanticData $semdata, $showfactbox = SMW_FACTBOX_NONEMPTY) {
		global $wgContLang;
		wfProfileIn("SMWFactbox::printFactbox (SMW)");
		switch ($showfactbox) {
			case SMW_FACTBOX_HIDDEN: // show never
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
			return;
			case SMW_FACTBOX_SPECIAL: // show only if there are special properties
				if (!$semdata->hasVisibleSpecialProperties()) {
					wfProfileOut("SMWFactbox::printFactbox (SMW)");
					return;
				}
			break;
			case SMW_FACTBOX_NONEMPTY: // show only if non-empty
				if ( (!$semdata->hasProperties()) && (!$semdata->hasVisibleSpecialProperties()) ) {
					wfProfileOut("SMWFactbox::printFactbox (SMW)");
					return;
				}
			break;
		// case SMW_FACTBOX_SHOWN: // just show ...
		}

		// actually build the Factbox text:
		wfLoadExtensionMessages('SemanticMediaWiki');
		smwfRequireHeadItem(SMW_HEADER_STYLE);
		$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . $semdata->getSubject()->getWikiValue(), 'rdflink');

		$browselink = SMWInfolink::newBrowsingLink($semdata->getSubject()->getText(), $semdata->getSubject()->getWikiValue(), 'swmfactboxheadbrowse');
		$text = '<div class="smwfact">' .
		        '<span class="smwfactboxhead">' . wfMsgForContent('smw_factbox_head', $browselink->getWikiText() ) . '</span>' .
		        '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
		        '<table class="smwfacttable">' . "\n";
		if ($semdata->hasProperties()  || $semdata->hasSpecialProperties()) {
			foreach($semdata->getProperties() as $key => $property) {
				if ($property instanceof Title) {
					$text .= '<tr><td class="smwpropname">[[' . $property->getPrefixedText() . '|' . preg_replace('/[ ]/u','&nbsp;',$property->getText(),2) . ']] </td><td class="smwprops">';
					/// NOTE: the preg_replace is a slight hack to ensure that the left column does not get too narrow
				} else { // special property
					if ($key{0} == '_') continue; // internal special property without label
					smwfRequireHeadItem(SMW_HEADER_TOOLTIP);
					$text .= '<tr><td class="smwspecname"><span class="smwttinline"><span class="smwbuiltin">[[' .
					          $wgContLang->getNsText(SMW_NS_PROPERTY) . ':' . $key . '|' . $key .
					          ']]</span><span class="smwttcontent">' . wfMsgForContent('smw_isspecprop') .
					          '</span></span></td><td class="smwspecs">';
				}

				$propvalues = $semdata->getPropertyValues($property);
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
		$text .= '</table></div>';
		wfProfileOut("SMWFactbox::printFactbox (SMW)");
		return $text;
	}

	/**
	 * This function creates wiki text suitable for rendering a Factbox based on the
	 * information found in a given OutputPage object. If the required custom data
	 * is not found in the given OutputPage, then semantic data for the provided Title 
	 * object is retreived from the store.
	 *
	 * $wgParser will be invoked, so this should not be called during parsing.
	 */
	static public function getFactboxTextFromOutput($outputpage, $title) {
		global $wgParser, $wgRequest, $smwgShowFactboxEdit, $smwgShowFactbox;
		if (!isset($outputpage->mSMWData) || $outputpage->mSMWData->stubobject) {
			$semdata = smwfGetStore()->getSemanticData($title);
		} else {
			$semdata = $outputpage->mSMWData;
		}
		$mws =  (isset($outputpage->mSMWMagicWords))?$outputpage->mSMWMagicWords:array();
		if (in_array('SMW_SHOWFACTBOX',$mws)) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		} elseif (in_array('SMW_NOFACTBOX',$mws)) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		} elseif ($wgRequest->getCheck('wpPreview')) {
			$showfactbox = $smwgShowFactboxEdit;
		} else {
			$showfactbox = $smwgShowFactbox;
		}
		$factbox = SMWFactbox::getFactboxText($semdata, $showfactbox);
		$popts = new ParserOptions();
		$parseroutput = $wgParser->parse( $factbox, $title, $popts );
		return $parseroutput->getText();
	}

	/**
	* This hook copies SMW's custom data from the given ParserOutput object to
	* the given OutputPage object, since otherwise it is not possible to access
	* it later on to build a Factbox.
	*/
	static public function onOutputPageParserOutput($outputpage, $parseroutput) {
		if (isset($parseroutput->mSMWData)) {
			$outputpage->mSMWData = $parseroutput->mSMWData;
		}
		if (isset($parseroutput->mSMWMagicWords)) {
			$outputpage->mSMWMagicWords = $parseroutput->mSMWMagicWords;
		}
		return true;
	}

	/**
	 * This hook is used for inserting the Factbox text directly after the wiki page.
	 */
	static public function onOutputPageBeforeHTML($outputpage, &$text) {
		global $wgTitle;
		$text .= SMWFactbox::getFactboxTextFromOutput($outputpage, $wgTitle);
		return true;
	}

	/**
	 * This hook is used for inserting the Factbox text after the article contents (including
	 * categories).
	 */
	static public function onSkinAfterContent(&$data) {
		global $wgOut, $wgTitle;
		$data .= SMWFactbox::getFactboxTextFromOutput($wgOut, $wgTitle);
		return true;
	}

}
