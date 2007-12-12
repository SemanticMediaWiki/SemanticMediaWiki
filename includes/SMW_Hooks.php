<?php
/**
 * @author Klaus Lassleben
 * @author Markus Krötzsch
 * @author Kai Hüner
 */

//// Parsing annotations

/**
*  This method will be called before an article is displayed or previewed.
*  For display and preview we strip out the semantic properties and append them
*  at the end of the article.
*
*  TODO: $strip_state is not used (and must not be used, since it is
*        not relevant when moving the hook to internalParse()).
*/
function smwfParserHook(&$parser, &$text, &$strip_state = null) {
	global $smwgIP;
	include_once($smwgIP . '/includes/SMW_Factbox.php');
	// Init global storage for semantic data of this article.
	SMWFactbox::initStorage($parser->getTitle());

	// In the regexp matches below, leading ':' escapes the markup, as
	// known for Categories.
	// Parse links to extract semantic properties
	$semanticLinkPattern = '(\[\[(([^:][^]]*):[=:])+((?:[^|\[\]]|\[\[[^]]*\]\]|\[[^]]*\])*)(\|([^]]*))?\]\])';
	$text = preg_replace_callback($semanticLinkPattern, 'smwfParsePropertiesCallback', $text);

	// print the results if enabled (we have to parse them in any case, in order to
	// clean the wiki source for further processing)
	if ( smwfIsSemanticsProcessed($parser->getTitle()->getNamespace()) ) {
		SMWFactbox::printFactbox($text);
	} else {
		SMWFactbox::clearStorage();
	}

	// add link to RDF to HTML header
	smwfRequireHeadItem('smw_rdf', '<link rel="alternate" type="application/rdf+xml" title="' .
						$parser->getTitle()->getPrefixedText() . '" href="' .
						$parser->getOptions()->getSkin()->makeSpecialUrl(
							'ExportRDF/' . $parser->getTitle()->getPrefixedText(), 'xmlmime=rdf'
						) . "\" />");

	return true; // always return true, in order not to stop MW's hook processing!
}

/**
* This callback function strips out the semantic attributes from a wiki
* link.
*/
function smwfParsePropertiesCallback($semanticLink) {
	global $smwgInlineErrors;
	wfProfileIn("smwfParsePropertiesCallback (SMW)");
	if (array_key_exists(2,$semanticLink)) {
		$property = $semanticLink[2];
	} else { $property = ''; }
	if (array_key_exists(3,$semanticLink)) {
		$value = $semanticLink[3];
	} else { $value = ''; }
	if (array_key_exists(5,$semanticLink)) {
		$valueCaption = $semanticLink[5];
	} else { $valueCaption = false; }

	//extract annotations and create tooltip
	$properties = preg_split('/:[=:]/', $property);
	foreach($properties as $singleprop) {
		$dv = SMWFactbox::addProperty($singleprop,$value,$valueCaption);
	}
	$result = $dv->getShortWikitext(true);
	if ( ($smwgInlineErrors) && (!$dv->isValid()) ) {
		$result .= $dv->getErrorText();
	}
	wfProfileOut("smwfParsePropertiesCallback (SMW)");
	return $result;
}


//// Saving, deleting, and moving articles


/**
 * Called before the article is saved. Allows us to check and remember whether an article is new.
 */
function smwfPreSaveHook(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags) {
	global $smwgIP;
	include_once($smwgIP . '/includes/SMW_Factbox.php'); // Normally this must have happened, but you never know ...
	if ($flags & EDIT_NEW) {
		SMWFactbox::setNewArticle();
	}
	return true; // always return true, in order not to stop MW's hook processing!
}


/**
*  This method will be called after an article is saved
*  and stores the semantic properties in the database. One
*  could consider creating an object for deferred saving
*  as used in other places of MediaWiki.
*/
function smwfSaveHook(&$article, &$user, &$text) {
	SMWFactbox::storeData(smwfIsSemanticsProcessed($article->getTitle()->getNamespace()));
	return true; // always return true, in order not to stop MW's hook processing!
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
	smwfGetStore()->changeTitle($old_title, $new_title);
	return true; // always return true, in order not to stop MW's hook processing!
}

// Special display for certain types of pages

/**
 * Register special classes for displaying semantic content on Property/Type pages
 */
function smwfShowListPage (&$title, &$article){
	global $smwgIP;
	if ($title->getNamespace() == SMW_NS_TYPE){
		smwfInitUserMessages();
		include_once($smwgIP . '/includes/articlepages/SMW_TypePage.php');
		$article = new SMWTypePage($title);
	} elseif ( $title->getNamespace() == SMW_NS_PROPERTY ) {
		smwfInitUserMessages();
		include_once($smwgIP . '/includes/articlepages/SMW_PropertyPage.php');
		$article = new SMWPropertyPage($title);
	}
	return true;
}



