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
	global $smwgIP, $smwgStoreAnnotations, $smwgTempStoreAnnotations, $smwgStoreActive;
	include_once($smwgIP . '/includes/SMW_Factbox.php');
	// Init global storage for semantic data of this article.
	SMWFactbox::initStorage($parser->getTitle());

	// print the results if enabled (we have to parse them in any case, in order to
	// clean the wiki source for further processing)
	if ( $smwgStoreActive && smwfIsSemanticsProcessed($parser->getTitle()->getNamespace()) ) {
		$smwgStoreAnnotations = true;
	} else {
		$smwgStoreAnnotations = false;
	}
	$smwgTempStoreAnnotations = true; // for [[SMW::on]] and [[SMW:off]]

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
	global $smwgIP;
	include_once($smwgIP . '/includes/SMW_Factbox.php'); // Normally this must have happened, but you never know ...
	if ($flags & EDIT_NEW) {
		SMWFactbox::setNewArticle();
	}
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 *  This method will be called after an article is saved
 *  and stores the semantic data in the database.
 *  If the saved article describes an attrbute or data type,
 *  the method checks wether the attribute type, the data type,
 *  the allowed values or the conversion factors have changed.
 *  If so, it triggers SMW_UpdateJobs for the relevant articles,
 *  which asynchronously update the semantic data in the database. 
 *  
 *  Known Bug -- TODO
 *  If an attribute is wrongly instanced. i.e.  it has an "Oops" message.
 *  It will not be found by the getAllAttributeSubjects method of the store
 *  object and thus there will be no Updatejobs triggered for it. 
 *  In order to resolve this, wrongly instanced properties need to be saved,
 *  too, so that they can be iterated. 
 */
function smwfSaveHook(&$article, &$user, &$text) {
	$title = $article->getTitle();
	$updatejobflag = 0;

	/**
	 * Checks if the semantic data has been changed.
	 * Sets the updateflag if so.
	 */

	$namespace = $article->getTitle()->getNamespace();

	if ($namespace == SMW_NS_PROPERTY || $namespace == SMW_NS_TYPE) {

		$oldstore = smwfGetStore()->getSemanticData($title);

		$oldproperties = $oldstore->getProperties($title); // returns only saved properties, properties that are not saved are not returned (e.g. when there is an error)
		$currentproperties = SMWFactbox :: $semdata->getProperties($title);
		//double side diff
		$diff = array_merge(array_diff($currentproperties, $oldproperties), array_diff($oldproperties, $currentproperties));

		//if any propery has changed the updateflag is set
		if (!empty ($diff)) { $updatejobflag = 1; }
		if ($updatejobflag == 0) {

			foreach ($oldproperties as $oldproperty) {
				$oldvalues[] = $oldstore->getPropertyValues($oldproperty);
			}

			foreach ($currentproperties as $currentproperty) {
				$currentvalues[] = SMWFactbox::$semdata->getPropertyValues($currentproperty);
			}

			//double side diff, if any propery value has changed the updateflag is set
			$diff2 = array_merge(array_diff_key($currentvalues[0], $oldvalues[0]), array_diff_key($oldvalues[0], $currentvalues[0]));

			if (!empty ($diff2)) {
				$updatejobflag = 1;
			}
		}
	}

	/**
	 * Saves the semantic data
	 */
	SMWFactbox :: storeData($title, smwfIsSemanticsProcessed($title->getNamespace()));

	/**
	 * Triggers the relevant Updatejobs if necessary
	 */
	if ($updatejobflag == 1) {
		$store = smwfGetStore();
		if ($namespace == SMW_NS_PROPERTY) {
			
			$subjects = $store->getAllPropertySubjects($title);

			foreach ($subjects as $subject) {
				smwfGenerateSMWUpdateJobs($subject);
			}

		} else
			if ($namespace == SMW_NS_TYPE) {

				$subjects = $store->getSpecialSubjects(SMW_SP_HAS_TYPE, $title);

				foreach ($subjects as $titlesofpropertypagestoupdate) {
					$subjectsPropertyPages = $store->getAllPropertySubjects($titlesofpropertypagestoupdate);
					smwfGenerateSMWUpdateJobs($titlesofpropertypagestoupdate);
					
					foreach ($subjectsPropertyPages as $titleOfPageToUpdate) {
						smwfGenerateSMWUpdateJobs($titleOfPageToUpdate);
					}

				}
			}
	}

	return true; // always return true, in order not to stop MW's hook processing!
}
 
/**
 * Generates a job, which update the semantic data of the responding page
 * 
 */
function smwfGenerateSMWUpdateJobs(& $title) {
	global $smwgIP;
	include_once($smwgIP . '/includes/Jobs/SMW_UpdateJob.php');
	$job = new SMW_UpdateJob($title);
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
		include_once($smwgIP . '/includes/articlepages/SMW_TypePage.php');
		$article = new SMWTypePage($title);
	} elseif ( $title->getNamespace() == SMW_NS_PROPERTY ) {
		include_once($smwgIP . '/includes/articlepages/SMW_PropertyPage.php');
		$article = new SMWPropertyPage($title);
	}
	return true;
}



