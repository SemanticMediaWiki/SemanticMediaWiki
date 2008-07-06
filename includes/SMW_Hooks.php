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
*/
function smwfParserHook(&$parser, &$text) {
	global $smwgStoreAnnotations, $smwgTempStoreAnnotations, $smwgStoreActive;
	SMWFactbox::initStorage($parser->getTitle()); // be sure we have our title, strange things happen in parsing

	// store the results if enabled (we have to parse them in any case, in order to
	// clean the wiki source for further processing)
	if ( $smwgStoreActive && smwfIsSemanticsProcessed($parser->getTitle()->getNamespace()) ) {
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
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_REDIRECTS_TO,$dv);
		}
	}

	// In the regexp matches below, leading ':' escapes the markup, as
	// known for Categories.
	// Parse links to extract semantic properties
	$semanticLinkPattern = '/\[\[               # Beginning of the link
	                        (([^:][^]]*):[=:])+ # Property name (can be nested?)
	                        (                   # After that:
	                          (?:[^|\[\]]       #   either normal text (without |, [ or ])
	                          |\[\[[^]]*\]\]    #   or a [[link]]
	                          |\[[^]]*\]        #   or an [external link]
	                        )*)                 # all this zero or more times
	                        (\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
	                        \]\]                # End of link
	                        /xu';
	$text = preg_replace_callback($semanticLinkPattern, 'smwfParsePropertiesCallback', $text);
	SMWFactbox::printFactbox($text);

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
* link.
*/
function smwfParsePropertiesCallback($semanticLink) {
	global $smwgInlineErrors, $smwgStoreAnnotations, $smwgTempStoreAnnotations;
	wfProfileIn("smwfParsePropertiesCallback (SMW)");
	if (array_key_exists(2,$semanticLink)) {
		$property = $semanticLink[2];
	} else { $property = ''; }
	if (array_key_exists(3,$semanticLink)) {
		$value = $semanticLink[3];
	} else { $value = ''; }

	if ($property == 'SMW') {
		switch ($value) {
			case 'on': $smwgTempStoreAnnotations = true; break;
			case 'off': $smwgTempStoreAnnotations = false; break;
		}
		wfProfileOut("smwfParsePropertiesCallback (SMW)");
		return '';
	}

	if (array_key_exists(5,$semanticLink)) {
		$valueCaption = $semanticLink[5];
	} else { $valueCaption = false; }

	//extract annotations and create tooltip
	$properties = preg_split('/:[=:]/u', $property);
	foreach($properties as $singleprop) {
		$dv = SMWFactbox::addProperty($singleprop,$value,$valueCaption, $smwgStoreAnnotations && $smwgTempStoreAnnotations);
	}
	$result = $dv->getShortWikitext(true);
	if ( ($smwgInlineErrors && $smwgStoreAnnotations && $smwgTempStoreAnnotations) && (!$dv->isValid()) ) {
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
	if ($flags & EDIT_NEW) {
		SMWFactbox::setNewArticle();
	}
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * Former hook used for storing data. Probably obsolete now (some testing required).
 * TODO: delete this when the current architecture has been tested sucessfully
 */
function smwfSaveHook(&$article, &$user, &$text) {
// 	smwfSaveDataForTitle($article->getTitle()); // done by LinksUpdate now
	return true;
}

/**
 * Used to updates data after changes of templates, but also at each saving of an article.
 */
function smwfLinkUpdateHook($links_update) {
	smwfSaveDataForTitle($links_update->mTitle);
	return true;
}

/**
 * Compares if two arrays of data values contain the same content.
 * Returns true if the two arrays contain the same data values,
 * false otherwise.
 */
function smwfEqualDatavalues($dv1, $dv2) {
	// The hashes of all values of both arrays are taken, then sorted
	// and finally concatenated, thus creating one long hash out of each
	// of the data value arrays. These are compared.
	$values = array();
	foreach($dv1 as $v) $values[] = $v->getHash();
	sort($values);
	$dv1hash = implode("___", $values);
	$values = array();
	foreach($dv2 as $v) $values[] = $v->getHash();
	sort($values);
	$dv2hash = implode("___", $values);
	
	return ($dv1hash == $dv2hash);	
}

/**
 * The generic safe method for some title. It is assumed that parsing has happened and that
 * SMWFactbox contains all relevant data. If the saved page describes a property or data type,
 * the method checks whether the property type, the data type, the allowed values, or the 
 * conversion factors have changed. If so, it triggers SMWUpdateJobs for the relevant articles,
 * which then asynchronously update the semantic data in the database.
 *
 *  Known Bug -- TODO
 *  Updatejobs are triggered when a property or type definition has
 *  changed, so that all affected pages get updated. However, if a page
 *  uses a property but the given value caused an error, then there is
 *  no record of that page using the property, so that it will not be
 *  updated. To fix this, one would need to store errors as well.
 */
function smwfSaveDataForTitle($title) {
	$namespace = $title->getNamespace();
	$updatejobflag = false;

	// Check if the semantic data has been changed.
	// Sets the updateflag to true if so.
	if ($namespace == SMW_NS_PROPERTY) {
		// if it is a property, then we need to check if the type or
		// the allowed values have been changed 
		$oldtype = smwfGetStore()->getSpecialValues($title, SMW_SP_HAS_TYPE);
		$newtype = SMWFactbox::$semdata->getPropertyValues(SMW_SP_HAS_TYPE);
				
		if (smwfEqualDatavalues($oldtype, $newtype)) {
			$updatejobflag = true;
		} else {
			$oldvalues = smwfGetStore()->getSpecialValues($title, SMW_SP_POSSIBLE_VALUE);
			$newvalues = SMWFactbox::$semdata->getPropertyValues(SMW_SP_POSSIBLE_VALUE);
			$updatejobflag = smwfEqualDatavalues($oldvalues, $newvalues);
		}
	}
		
	if ($namespace == SMW_NS_TYPE) {
		// if it is a type we need to check if the conversion factors have been changed
		$oldfactors = smwfGetStore()->getSpecialValues($title, SMW_SP_CONVERSION_FACTOR);
		$newfactors = SMWFactbox::$semdata->getPropertyValues(SMW_SP_CONVERSION_FACTOR);
		
		$updatejobflag = smwfEqualDatavalues($oldfactors, $newfactors);
	}

	// Save semantic data
	SMWFactbox::storeData(smwfIsSemanticsProcessed($title->getNamespace()));

	// Trigger relevant Updatejobs if necessary
	if ($updatejobflag) {
		$store = smwfGetStore();
		if ($namespace == SMW_NS_PROPERTY) {
			$subjects = $store->getAllPropertySubjects($title);
			foreach ($subjects as $subject) {
				smwfGenerateSMWUpdateJobs($subject);
			}
		} elseif ($namespace == SMW_NS_TYPE) {
			$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE,$title->getDBkey());
			$subjects = $store->getSpecialSubjects(SMW_SP_HAS_TYPE, $dv);
			foreach ($subjects as $titlesofpropertypagestoupdate) {
				$subjectsPropertyPages = $store->getAllPropertySubjects($titlesofpropertypagestoupdate);
				smwfGenerateSMWUpdateJobs($titlesofpropertypagestoupdate);
				foreach ($subjectsPropertyPages as $titleOfPageToUpdate) {
					smwfGenerateSMWUpdateJobs($titleOfPageToUpdate);
				}
			}
		}
	}
	return true;
}

/**
 * Generates a job, which update the semantic data of the responding page
 */
function smwfGenerateSMWUpdateJobs(& $title) {
	$job = new SMWUpdateJob($title);
	$job->insert();
}

/**
*  Restore semantic data if articles are undeleted.
*/
function smwfUndeleteHook(&$title, $create) {
	if ($create) {
		SMWFactbox::setNewArticle();
	}
	SMWFactbox::storeData(smwfIsSemanticsProcessed($title->getNamespace()));
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
	}
	return true;
}



