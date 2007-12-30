<?php
/**
 * The class in this file manages the parsing, displaying, and storing of semantic
 * data that is usually displayed within the factbox.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
include_once($smwgIP . '/includes/SMW_SemanticData.php');

/**
 * Static class for representing semantic data, which accepts user
 * inputs and provides methods for printing and storing its contents.
 * Its main purpose is to provide a persistent storage to keep semantic
 * data between hooks for parsing and storing.
 */
class SMWFactbox {

	/**
	 * The actual container for the semantic annotations. Public, since
	 * it is ref-passed to others for further processing.
	 */
	static $semdata;
	/**
	 * True if the respective article is newly created. This affects some
	 * storage operations.
	 */
	static protected $m_new;

	/**
	 * Initialisation method. Must be called before anything else happens.
	 */
	static function initStorage($title) {
		SMWFactbox::$semdata = new SMWSemanticData($title);
		SMWFactbox::$m_new   = false;
	}

	/**
	 * Clear all stored data
	 */
	static function clearStorage() {
		global $smwgStoreActive;
		if ($smwgStoreActive) {
			SMWFactbox::$semdata->clear();
		}
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

	/**
	 * This method adds a new property with the given value to the storage.
	 * It returns an array which contains the result of the operation in
	 * various formats.
	 */
	static function addProperty($propertyname, $value, $caption, $storeannotation = true) {
		wfProfileIn("SMWFactbox::addProperty (SMW)");
		global $smwgContLang, $smwgIP;
		include_once($smwgIP . '/includes/SMW_DataValueFactory.php');
		// See if this property is a special one, such as e.g. "has type"
		$propertyname = smwfNormalTitleText($propertyname); //slightly normalize label
		$special = $smwgContLang->findSpecialPropertyID($propertyname);

		switch ($special) {
			case false: // normal property
				$result = SMWDataValueFactory::newPropertyValue($propertyname,$value,$caption);
				if ($storeannotation) {
					SMWFactbox::$semdata->addPropertyValue($propertyname,$result);
				}
				wfProfileOut("SMWFactbox::addProperty (SMW)");
				return $result;
			case SMW_SP_IMPORTED_FROM: // this requires special handling
				$result = SMWFactbox::addImportedDefinition($value,$caption,$storeannotation);
				wfProfileOut("SMWFactbox::addProperty (SMW)");
				return $result;
			default: // generic special property
				$result = SMWDataValueFactory::newSpecialValue($special,$value,$caption);
				if ($storeannotation) {
					SMWFactbox::$semdata->addSpecialValue($special,$result);
				}
				wfProfileOut("SMWFactbox::addProperty (SMW)");
				return $result;
		}
	}

	/**
	 * This method adds multiple special properties needed to use the given
	 * article for representing an element from a whitelisted external
	 * ontology element. It does various feasibility checks (typing etc.)
	 * and returns a "virtual" value object that can be used for printing
	 * in text. Although many property values are added, not all are printed in
	 * the factbox, since some do not have a translated name (and thus also
	 * could not be specified directly).
	 */
	static private function addImportedDefinition($value,$caption,$storeannotation) {
		global $wgContLang;

		list($onto_ns,$onto_section) = explode(':',$value,2);
		$msglines = preg_split("([\n][\s]?)",wfMsgForContent("smw_import_$onto_ns")); // get the definition for "$namespace:$section"

		if ( count($msglines) < 2 ) { //error: no elements for this namespace
			/// TODO: use new Error DV
			$datavalue = SMWDataValueFactory::newTypeIDValue('__err',$value,$caption);
			$datavalue->addError(wfMsgForContent('smw_unknown_importns',$onto_ns));
			if ($storeannotation) {
				SMWFactbox::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM,$datavalue);
			}
			return $datavalue;
		}

		list($onto_uri,$onto_name) = explode('|',array_shift($msglines),2);
		if ( ' ' == $onto_uri[0]) $onto_uri = mb_substr($onto_uri,1); // tolerate initial space
		$elemtype = -1;
		$datatype = NULL;
		foreach ( $msglines as $msgline ) {
			list($secname,$typestring) = explode('|',$msgline,2);
			if ( $secname === $onto_section ) {
				list($namespace, ) = explode(':',$typestring,2);
				// check whether type matches
				switch ($namespace) {
					case $wgContLang->getNsText(SMW_NS_TYPE):
						$elemtype = SMW_NS_PROPERTY;
						$datatype = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE, $typestring);
						break;
					case $wgContLang->getNsText(SMW_NS_PROPERTY):
						$elemtype = SMW_NS_PROPERTY;
						break;
					case $wgContLang->getNsText(NS_CATEGORY):
						$elemtype = NS_CATEGORY;
						break;
					default: // match all other namespaces
						$elemtype = NS_MAIN;
				}
				break;
			}
		}

		// check whether element of correct type was found
		$this_ns = SMWFactbox::$semdata->getSubject()->getNamespace();
		$error = NULL;
		switch ($elemtype) {
			case SMW_NS_PROPERTY: case NS_CATEGORY:
				if ($this_ns != $elemtype) {
					$error = wfMsgForContent('smw_nonright_importtype',$value, $wgContLang->getNsText($elemtype));
				}
				break;
			case NS_MAIN:
				if ( (SMW_NS_PROPERTY == $this_ns) || (NS_CATEGORY == $this_ns)) {
					$error = wfMsgForContent('smw_wrong_importtype',$value, $wgContLang->getNsText($this_ns));
				}
				break;
			case -1:
				$error = wfMsgForContent('smw_no_importelement',$value);
		}

		if (NULL != $error) {
			$datavalue = SMWDataValueFactory::newTypeIDValue('__err',$value,$caption);
			$datavalue->addError($error);
			if ($storeannotation) {
				SMWFactbox::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM, $datavalue);
			}
			return $datavalue;
		}

		if ($storeannotation) {
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_BASEURI,SMWDataValueFactory::newTypeIDValue('_str',$onto_uri));
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_NSID,SMWDataValueFactory::newTypeIDValue('_str',$onto_ns));
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_SECTION,SMWDataValueFactory::newTypeIDValue('_str',$onto_section));
			if (NULL !== $datatype) {
				SMWFactbox::$semdata->addSpecialValue(SMW_SP_HAS_TYPE,$datatype);
			}
		}
		// print the input (this property is usually not stored, see SMW_SQLStore.php)
		$datavalue = SMWDataValueFactory::newTypeIDValue('_str',"[$onto_uri$onto_section $value] ($onto_name)",$caption);
		if ($storeannotation) {
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM, $datavalue);
		}
		return $datavalue;
	}

//// Methods for printing the content of this object into an factbox   */

	/**
	 * This method prints semantic data at the bottom of an article.
	 */
	static function printFactbox(&$text) {
		global $wgContLang, $wgServer, $smwgShowFactbox, $smwgShowFactboxEdit, $smwgStoreActive, $smwgIP, $wgRequest;
		if (!$smwgStoreActive) return;

		if ( $wgRequest->getCheck('wpPreview') ) {
			$showfactbox = $smwgShowFactboxEdit;
		} else {
			$showfactbox = $smwgShowFactbox;
		}

		wfProfileIn("SMWFactbox::printFactbox (SMW)");
		switch ($showfactbox) {
		case SMW_FACTBOX_HIDDEN: // never
			wfProfileOut("SMWFactbox::printFactbox (SMW)");
			return;
		case SMW_FACTBOX_SPECIAL: // only when there are special properties
			if ( !SMWFactbox::$semdata->hasSpecialProperties() ) {
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
				return;
			}
			break;
		case SMW_FACTBOX_NONEMPTY: // only when non-empty
			if ( (!SMWFactbox::$semdata->hasProperties()) && (!SMWFactbox::$semdata->hasSpecialProperties()) ) {
				wfProfileOut("SMWFactbox::printFactbox (SMW)");
				return;
			}
			break;
		// case SMW_FACTBOX_SHOWN: display
		}

		smwfRequireHeadItem(SMW_HEADER_STYLE);
		include_once($smwgIP . '/includes/SMW_Infolink.php');
		$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . str_replace('%2F', '/', rawurlencode(SMWFactbox::$semdata->getSubject()->getPrefixedText())), 'rdflink');

		$browselink = SMWInfolink::newBrowsingLink(SMWFactbox::$semdata->getSubject()->getText(), SMWFactbox::$semdata->getSubject()->getPrefixedText(), 'swmfactboxheadbrowse');
		// The "\n" is to ensure that lists on the end of articles are terminated
		// before the div starts. It would of course be much cleaner to print the
		// factbox in another way, similar to the way that categories are printed
		// now. However, this would require more patching of MediaWiki code ...
		$text .= "\n" . '<div class="smwfact">' .
		         '<span class="smwfactboxhead">' . wfMsgForContent('smw_factbox_head', $browselink->getWikiText() ) . '</span>' .
		         '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
		         '<table class="smwfacttable">' . "\n";
		SMWFactbox::printProperties($text);
		$text .= '</table></div>';
		wfProfileOut("SMWFactbox::printFactbox (SMW)");
	}

	/**
	 * This method prints (special) property values at the bottom of an article.
	 */
	static protected function printProperties(&$text) {
		if (!SMWFactbox::$semdata->hasProperties() && !SMWFactbox::$semdata->hasSpecialProperties()) {
			return;
		}
		global $wgContLang;

		foreach(SMWFactbox::$semdata->getProperties() as $key => $property) {
			$text .= '<tr><td class="smwpropname">';
			if ($property instanceof Title) {
				$text .= '<tr><td class="smwpropname">[[' . $property->getPrefixedText() . '|' . preg_replace('/[\s]/','&nbsp;',$property->getText(),2) . ']] </td><td class="smwprops">';
				// TODO: the preg_replace is a kind of hack to ensure that the left column does not get too narrow; maybe we can find something nicer later
			} else { // special property
				if ($key{0} == '_') continue; // internal special property without label
				smwfRequireHeadItem(SMW_HEADER_TOOLTIP);
				$text .= '<tr><td class="smwspecname"><span class="smwttinline"><span class="smwbuiltin">[[' .
				          $wgContLang->getNsText(SMW_NS_PROPERTY) . ':' . $key . '|' . $key .
				          ']]</span><span class="smwttcontent">' . wfMsgForContent('smw_isspecprop') .
				          '</span></span></td><td class="smwspecs">';
			}

			$propvalues = SMWFactbox::$semdata->getPropertyValues($property);
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

//// Methods for writing the content of this object

	/**
	 * This method stores the semantic data, and clears any outdated entries
	 * for the current article.
	 * @TODO: is $title still needed, since we now have SMWFactbox::$title? Could they differ significantly?
	 */
	static function storeData($processSemantics) {
		// clear data even if semantics are not processed for this namespace
		// (this setting might have been changed, so that data still exists)
		$title = SMWFactbox::$semdata->getSubject();
		if ($processSemantics) {
			smwfGetStore()->updateData(SMWFactbox::$semdata, SMWFactbox::$m_new);
		} elseif (!SMWFactbox::$m_new) {
			smwfGetStore()->deleteSubject($title);
		}
	}

	/**
	 * Delete semantic data currently associated with some article.
	 */
	static function clearData($s_title) {
		smwfGetStore()->deleteSubject($s_title);
	}

}


