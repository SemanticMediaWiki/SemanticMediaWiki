<?php
/**
 * This file contains various global functions that are used in hooks.
 * @file
 * @ingroup SMW
 * @author Klaus Lassleben
 * @author Markus Krötzsch
 * @author Kai Hüner
 */

//// Parsing annotations

/**
*  This method will be called before an article is displayed or previewed.
*  For display and preview we strip out the semantic properties and append them
*  at the end of the article.
*/
function smwfParserHook(&$parser, &$text) {
	global $smwgStoreAnnotations, $smwgTempStoreAnnotations, $smwgLinksInValues, $smwgTempParser;

	// store the results if enabled (we have to parse them in any case, in order to
	// clean the wiki source for further processing)
	if ( smwfIsSemanticsProcessed($parser->getTitle()->getNamespace()) ) {
		$smwgStoreAnnotations = true;
	} else {
		$smwgStoreAnnotations = false;
	}
	$smwgTempStoreAnnotations = true; // used for [[SMW::on]] and [[SMW:off]]

	// process redirects, if any
	// (it seems that there is indeed no more direct way of getting this info from MW)
	$rt = Title::newFromRedirect($text);
	if ($rt !== NULL) {
		$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_REDIRECTS_TO,$rt->getPrefixedText());
		if ($smwgStoreAnnotations) {
			SMWParseData::getSMWData($parser)->addSpecialValue(SMW_SP_REDIRECTS_TO,$dv);
		}
	}

	$smwgTempParser = $parser; // only used in subsequent callbacks, forgotten afterwards
	// In the regexp matches below, leading ':' escapes the markup, as
	// known for Categories.
	// Parse links to extract semantic properties
	if ($smwgLinksInValues) { // more complex regexp -- lib PCRE may cause segfaults if text is long :-(
		$semanticLinkPattern = '/\[\[                 # Beginning of the link
		                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
		                        (                     # After that:
		                          (?:[^|\[\]]         #   either normal text (without |, [ or ])
		                          |\[\[[^]]*\]\]      #   or a [[link]]
		                          |\[[^]]*\]          #   or an [external link]
		                        )*)                   # all this zero or more times
		                        (?:\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
		                        \]\]                  # End of link
		                        /xu';
		$text = preg_replace_callback($semanticLinkPattern, 'smwfParsePropertiesCallback', $text);
	} else { // simpler regexps -- no segfaults found for those, but no links in values
		$semanticLinkPattern = '/\[\[                 # Beginning of the link
		                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
		                        ([^\[\]]*)            # content: anything but [, |, ]
		                        \]\]                  # End of link
		                        /xu';
		$text = preg_replace_callback($semanticLinkPattern, 'smwfSimpleParsePropertiesCallback', $text);
	}
	SMWFactbox::printFactbox($text, $parser);

	// add link to RDF to HTML header
	smwfRequireHeadItem('smw_rdf', '<link rel="alternate" type="application/rdf+xml" title="' .
	                    $parser->getTitle()->getPrefixedText() . '" href="' .
	                    htmlspecialchars($parser->getOptions()->getSkin()->makeSpecialUrl(
	                       'ExportRDF/' . $parser->getTitle()->getPrefixedText(), 'xmlmime=rdf'
	                    )) . "\" />");

	return true; // always return true, in order not to stop MW's hook processing!
}

/**
* This callback function strips out the semantic attributes from a wiki
* link. Expected parameter: array(linktext, properties, value|caption)
* This function is a preprocessing for smwfParsePropertiesCallback, and
* takes care of separating value and caption (instead of leaving this to
* a more complex regexp).
*/
function smwfSimpleParsePropertiesCallback($semanticLink) {
	$value = '';
	$caption = false;
	if (array_key_exists(2,$semanticLink)) {
		$parts = explode('|',$semanticLink[2]);
		if (array_key_exists(0,$parts)) {
			$value = $parts[0];
		}
		if (array_key_exists(1,$parts)) {
			$caption = $parts[1];
		}
	}
	if ($caption !== false) {
		return smwfParsePropertiesCallback(array($semanticLink[0],$semanticLink[1],$value,$caption));
	} else {
		return smwfParsePropertiesCallback(array($semanticLink[0],$semanticLink[1],$value));
	}
}

/**
* This callback function strips out the semantic attributes from a wiki
* link. Expected parameter: array(linktext, properties, value, caption)
*/
function smwfParsePropertiesCallback($semanticLink) {
	global $smwgInlineErrors, $smwgStoreAnnotations, $smwgTempStoreAnnotations, $smwgTempParser;
	wfProfileIn("smwfParsePropertiesCallback (SMW)");
	if (array_key_exists(1,$semanticLink)) {
		$property = $semanticLink[1];
	} else { $property = ''; }
	if (array_key_exists(2,$semanticLink)) {
		$value = $semanticLink[2];
	} else { $value = ''; }

	if ($property == 'SMW') {
		switch ($value) {
			case 'on': $smwgTempStoreAnnotations = true; break;
			case 'off': $smwgTempStoreAnnotations = false; break;
		}
		wfProfileOut("smwfParsePropertiesCallback (SMW)");
		return '';
	}

	if (array_key_exists(3,$semanticLink)) {
		$valueCaption = $semanticLink[3];
	} else { $valueCaption = false; }

	//extract annotations and create tooltip
	$properties = preg_split('/:[=:]/u', $property);
	foreach($properties as $singleprop) {
		$dv = SMWParseData::addProperty($singleprop,$value,$valueCaption, $smwgTempParser, $smwgStoreAnnotations && $smwgTempStoreAnnotations);
	}
	$result = $dv->getShortWikitext(true);
	if ( ($smwgInlineErrors && $smwgStoreAnnotations && $smwgTempStoreAnnotations) && (!$dv->isValid()) ) {
		$result .= $dv->getErrorText();
	}
	wfProfileOut("smwfParsePropertiesCallback (SMW)");
	return $result;
}

/**
 * Adds a block whenever a gallery will be started, otherwise the gallery may cause
 * a factbox to be rendered. The smwgBlockFactBox is checked i
 */
function smwfBlockFactboxFromImageGallery(  ) {
	SMWFactbox::blockOnce();
	return true;
}

//// Saving, deleting, and moving articles


/**
 * Called before the article is saved. Allows us to check and remember whether an article is new.
 */
function smwfPreSaveHook(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags) {
	if ($flags & EDIT_NEW) {
		SMWFactbox::setNewArticle();
	}
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * Used to updates data after changes of templates, but also at each saving of an article.
 */
function smwfLinkUpdateHook($links_update) {
	SMWParseData::storeData($links_update->mParserOutput, $links_update->mTitle, true);
	return true;
}

/**
*  This method will be called whenever an article is deleted so that
*  semantic properties are cleared appropriately.
*/
function smwfDeleteHook(&$article, &$user, &$reason) {
	smwfGetStore()->deleteSubject($article->getTitle());
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
*  This method will be called whenever an article is moved so that
*  semantic properties are moved accordingly.
*/
function smwfMoveHook(&$old_title, &$new_title, &$user, $pageid, $redirid) {
	smwfGetStore()->changeTitle($old_title, $new_title, $pageid, $redirid);
	return true; // always return true, in order not to stop MW's hook processing!
}

// Special display for certain types of pages

/**
 * Register special classes for displaying semantic content on Property/Type pages
 */
function smwfShowListPage (&$title, &$article){
	global $smwgIP;
	if ($title->getNamespace() == SMW_NS_TYPE){
		$article = new SMWTypePage($title);
	} elseif ( $title->getNamespace() == SMW_NS_PROPERTY ) {
		$article = new SMWPropertyPage($title);
	} elseif ( $title->getNamespace() == SMW_NS_CONCEPT ) {
		$article = new SMWConceptPage($title);
	}
	return true;
}



