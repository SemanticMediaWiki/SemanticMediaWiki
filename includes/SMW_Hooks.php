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
	global $smwgIP, $smwgStoreAnnotations, $smwgTempStoreAnnotations, $smwgStoreActive;
	include_once($smwgIP . '/includes/SMW_Factbox.php');
	// Init global storage for semantic data of this article.
	SMWFactbox::initStorage($parser->getTitle());

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
	global $smwgIP;
	include_once($smwgIP . '/includes/SMW_Factbox.php'); // Normally this must have happened, but you never know ...
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

	// Check if the semantic data has been changed. Sets the updateflag if so.
	if ($namespace == SMW_NS_PROPERTY || $namespace == SMW_NS_TYPE) {
		$oldstore = smwfGetStore()->getSemanticData($title);

		$oldproperties = $oldstore->getProperties($title); // returns only saved properties, properties that are not saved are not returned (e.g. when there is an error)
		$currentproperties = SMWFactbox::$semdata->getProperties($title);
		
		$oldcount = count($oldproperties);
		$newcount = count($currentproperties);
		if ($oldcount == $newcount) {
			
			if ($oldcount > 0) {
				//double side diff
				$diff = array_merge(array_diff($currentproperties, $oldproperties), array_diff($oldproperties, $currentproperties));

				//if any propery has changed the updateflag is set
				if (!empty ($diff)) {
					$updatejobflag = true;
				} else {
					foreach ($oldproperties as $oldproperty) {
						$oldvalues[] = $oldstore->getPropertyValues($oldproperty);
					}
					foreach ($currentproperties as $currentproperty) {
						$currentvalues[] = SMWFactbox::$semdata->getPropertyValues($currentproperty);
					}
					//double side diff, if any propery value has changed the updateflag is set
					$diff = array_merge(array_diff_key($currentvalues[0], $oldvalues[0]), array_diff_key($oldvalues[0], $currentvalues[0]));
					if (!empty ($diff)) { $updatejobflag = true; }
				}
			}

		} else {
			$updatejobflag = true;
		}
		
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
		include_once($smwgIP . '/includes/articlepages/SMW_TypePage.php');
		$article = new SMWTypePage($title);
	} elseif ( $title->getNamespace() == SMW_NS_PROPERTY ) {
		include_once($smwgIP . '/includes/articlepages/SMW_PropertyPage.php');
		$article = new SMWPropertyPage($title);
	}
	return true;
}



