<?php
/**
 * The class in this file manages the parsing, displaying, and storing of semantic
 * data that is usually displayed within the factbox.
 *
 * @author Markus Krötzsch
 */

require_once('SMW_SemanticData.php');

/**
 * Static class for representing semantic data, which accepts user
 * inputs and provides methods for printing and storing its contents.
 * Its main purpose is to provide a persistent storage to keep semantic
 * data between hooks for parsing and storing.
 */
class SMWFactbox {

	/**
	 * The actual contained for the semantic annotations. Public, since
	 * it is ref-passed to othes for further processing.
	 */
	static $semdata;
	/**
	 * The skin that is to be used for output functions.
	 */
	static protected $skin;

	/**
	 * Initialisation method. Must be called before anything else happens.
	 */
	static function initStorage($title, $skin) {
		SMWFactbox::$semdata = new SMWSemanticData($title);
		SMWFactbox::$skin = $skin;
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

//// Methods for adding data to the object

	/**
	 * This method adds a new attribute with the given value to the storage.
	 * It returns an array which contains the result of the operation in
	 * various formats.
	 */
	static function addAttribute($attribute, $value, $caption) {
		global $smwgContLang, $smwgStoreActive;
		// See if this attribute is a special one like e.g. "Has unit"
		$attribute = smwfNormalTitleText($attribute); //slightly normalize label
		$specprops = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($attribute, $specprops);

		switch ($special) {
			case false: // normal attribute
				$result = SMWDataValueFactory::newAttributeValue($attribute,$value,$caption);
				if ($smwgStoreActive) {
					SMWFactbox::$semdata->addAttributeTextValue($attribute,$result);
				}
				return $result;
			case SMW_SP_IMPORTED_FROM: // this requires special handling
				return SMWFactbox::addImportedDefinition($value,$caption);
			default: // generic special attribute
				if ( $special === SMW_SP_SERVICE_LINK ) { // do some custom formatting in this case
					global $wgContLang;
					$v = str_replace(' ', '_', $value); //normalize slightly since messages distinguish '_' and ' '
					$result = SMWDataValueFactory::newSpecialValue($special,$v,$caption);
					$v = $result->getXSDValue(); //possibly further sanitized, so let's be cautious
					$result->setProcessedValues($value,$v); //set user value back to the input version
					$result->setPrintoutString('[[' . $wgContLang->getNsText(NS_MEDIAWIKI) . ':smw_service_' . $v . "|$value]]");
				} else { // standard processing
					$result = SMWDataValueFactory::newSpecialValue($special,$value,$caption);
				}
				if ($smwgStoreActive) {
					SMWFactbox::$semdata->addSpecialValue($special,$result);
				}
				return $result;
		}
	}

	/**
	 * This method adds a new relation with the given target to the storage.
	 */
	static function addRelation($relation, $target) {
		global $smwgContLang, $smwgStoreActive;
		if (!$smwgStoreActive) return; // no action required
		// See if this relation is a special one like e.g. "Has type"
		$relation = smwfNormalTitleText($relation);
		$srels = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($relation, $srels);
		$value = SMWDataValueFactory::newTypeIDValue('_wpg',$target, false, $relation);

		if ($special !== false) {
			$type = SMWTypeHandlerFactory::getSpecialTypeHandler($special);
			if ( ($type->getID() != 'error') && ($special != SMW_SP_HAS_TYPE) ) { //Oops! This is not a relation!
				//Note that this still changes the behaviour, since the [[ ]]
				//are not removed! A cleaner solution would be to print a
				//helpful message into the factbox, based on a new "print value as
				//error" datatype handler. FIXME
				SMWFactbox::addAttribute($relation, $target, false);
			} else {
				SMWFactbox::$semdata->addSpecialValue($special, $value);
			}
		} else {
			SMWFactbox::$semdata->addRelationTextValue($relation, $value);
		}
	}

	/**
	 * This method adds multiple special properties needed to use the given
	 * article for representing an element from a whitelisted external
	 * ontology element. It does various feasibility checks (typing etc.)
	 * and returns a "virtual" value object that can be used for printing
	 * in text. Although many attributes are added, not all are printed in
	 * the factbox, since some do not have a translated name (and thus also
	 * could not be specified directly).
	 */
	static private function addImportedDefinition($value,$caption) {
		global $wgContLang, $smwgStoreActive;

		list($onto_ns,$onto_section) = explode(':',$value,2);
		$msglines = preg_split("([\n][\s]?)",wfMsgForContent("smw_import_$onto_ns")); // get the definition for "$namespace:$section"

		if ( count($msglines) < 2 ) { //error: no elements for this namespace
			/// TODO: use new Error DV
			$datavalue = SMWDataValueFactory::newTypeHandlerValue(new SMWErrorTypeHandler(wfMsgForContent('smw_unknown_importns',$onto_ns)),$value,$caption);
			if ($smwgStoreActive) {
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
						$elemtype = SMW_NS_ATTRIBUTE;
						$datatype = Title::newFromText($typestring, SMW_NS_TYPE);
						break;
					case $wgContLang->getNsText(SMW_NS_ATTRIBUTE): // wrong: we need a datatype
						break;
					case $wgContLang->getNsText(SMW_NS_RELATION):
						$elemtype = SMW_NS_RELATION;
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
			case SMW_NS_ATTRIBUTE: case SMW_NS_RELATION: case NS_CATEGORY:
				if ($this_ns != $elemtype) {
					$error = wfMsgForContent('smw_nonright_importtype',$value, $wgContLang->getNsText($elemtype));
				}
				break;
			case NS_MAIN:
				if ( (SMW_NS_ATTRIBUTE == $this_ns) || (SMW_NS_RELATION == $this_ns) || (NS_CATEGORY == $this_ns)) {
					$error = wfMsgForContent('smw_wrong_importtype',$value, $wgContLang->getNsText($this_ns));
				}
				break;
			case -1:
				$error = wfMsgForContent('smw_no_importelement',$value);
		}

		if (NULL != $error) {
			/// TODO: use new Error DV
			$datavalue = SMWDataValueFactory::newTypeHandlerValue(new SMWErrorTypeHandler($error),$value,$caption);
			if ($smwgStoreActive) {
				SMWFactbox::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM, $datavalue);
			}
			return $datavalue;
		}


		///TODO: use new DVs

		// Note: the following just overwrites any existing values for the given
		// special properties, since they can only have one value anyway; this
		// might hide errors -- should we care?
		$sth = new SMWStringTypeHandler(); // making one is enough ...

		if ($smwgStoreActive) {
			$datavalue = SMWDataValueFactory::newTypeHandlerValue($sth,$onto_uri);
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_BASEURI,$datavalue);
			$datavalue = SMWDataValueFactory::newTypeHandlerValue($sth,$onto_ns);
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_NSID,$datavalue);
			$datavalue = SMWDataValueFactory::newTypeHandlerValue($sth,$onto_section);
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_EXT_SECTION,$datavalue);
			if (NULL !== $datatype) {
				SMWFactbox::$semdata->addSpecialValue(SMW_SP_HAS_TYPE,$datatype);
			}
		}

		// print the input (this property is usually not stored, see SMW_SQLStore.php)
		$datavalue = SMWDataValueFactory::newTypeHandlerValue($sth,"[$onto_uri$onto_section $value]",$caption);
		// TODO: Unfortunatelly, the following line can break the tooltip code if $onto_name has markup. -- mak
		// if ('' != $onto_name) $datavalue->setPrintoutString($onto_name, 'onto_name');
		if ('' != $onto_name) $datavalue->setPrintoutString("[$onto_uri$onto_section $value] ($onto_name)");
		if ($smwgStoreActive) {
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM, $datavalue);
		}
		return $datavalue;
	}

//// Methods for printing the content of this object into an factbox   */

	/**
	 * This method prints semantic data at the bottom of an article.
	 */
	static function printFactbox(&$text) {
		global $wgContLang, $wgServer, $smwgShowFactbox, $smwgStoreActive;
		if (!$smwgStoreActive) return true;
		switch ($smwgShowFactbox) {
		case SMW_FACTBOX_HIDDEN: return true;
		case SMW_FACTBOX_NONEMPTY:
			if ( (!SMWFactbox::$semdata->hasRelations()) && (!SMWFactbox::$semdata->hasAttributes()) && (!SMWFactbox::$semdata->hasSpecialProperties()) ) {
				return;
			}
		}

		$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . str_replace('%2F', '/', urlencode(SMWFactbox::$semdata->getSubject()->getPrefixedText())), 'rdflink');

		$browselink = SMWInfolink::newBrowsingLink(SMWFactbox::$semdata->getSubject()->getText(), SMWFactbox::$semdata->getSubject()->getPrefixedText(), 'swmfactboxheadbrowse');
		// The "\n" is to ensure that lists on the end of articles are terminated
		// before the div starts. It would of course be much cleaner to print the
		// factbox in another way, similar to the way that categories are printed
		// now. However, this would require more patching of MediaWiki code ...
		$text .= "\n" . '<div class="smwfact">' .
		         '<span class="smwfactboxhead">' . wfMsgForContent('smw_factbox_head', $browselink->getWikiText() ) . '</span>' .
		         '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
		         '<table class="smwfacttable">' . "\n";
		SMWFactbox::printRelations($text);
		SMWFactbox::printAttributes($text);
		SMWFactbox::printSpecialProperties($text);
		$text .= '</table></div>';
	}

	/**
	 * This method prints attribute values at the bottom of an article.
	 */
	static protected function printAttributes(&$text) {
		if (!SMWFactbox::$semdata->hasAttributes()) {
			return;
		}

		//$text .= ' <tr><th class="atthead"></th><th class="atthead">' . wfMsgForContent('smw_att_head') . "</th></tr>\n";

		foreach(SMWFactbox::$semdata->getAttributes() as $attribute) {
			$attributeValueArray = SMWFactbox::$semdata->getAttributeValues($attribute);
			$text .= '<tr><td class="smwattname">';
			$text .= '   [[' . $attribute->getPrefixedText() . '|' . preg_replace('/[\s]/','&nbsp;',$attribute->getText(),2) . ']] </td><td class="smwatts">';
			// TODO: the preg_replace is a kind of hack to ensure that the left column does not get too narrow; maybe we can find something nicer later

			$l = count($attributeValueArray);
			$i=0;
			foreach ($attributeValueArray as $attributeValue) {
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else {
						$text .= ', ';
					}
				}
				$i+=1;

				$text .= $attributeValue->getLongWikiText(true);

				$sep = '<!-- -->&nbsp;&nbsp;'; // the comment is needed to prevent MediaWiki from linking URL-strings together with the nbsps!
				foreach ($attributeValue->getInfolinks() as $link) {
					$text .= $sep . $link->getWikiText();
					$sep = ' &nbsp;&nbsp;'; // allow breaking for longer lists of infolinks
				}
			}
			$text .= '</td></tr>';
		}
	}

	/**
	 * This method prints semantic relations at the bottom of an article.
	 */
	static protected function printRelations(&$text) {
		if(!SMWFactbox::$semdata->hasRelations()) { return true; }

		//$text .= ' <tr><th class="relhead"></th><th class="relhead">' . wfMsgForContent('smw_rel_head') . "</th></tr>\n";
		
		foreach(SMWFactbox::$semdata->getRelations() as $relation) {
			$relValueArray = SMWFactbox::$semdata->getRelationValues($relation);
			$text .= '<tr><td class="smwattname">';
			$text .= '   [[' . $relation->getPrefixedText() . '|' . preg_replace('/[\s]/','&nbsp;',$relation->getText(),2) . ']] </td><td class="smwatts">';
			// TODO: the preg_replace is a kind of hack to ensure that the left column does not get too narrow; maybe we can find something nicer later

			$l = count($relValueArray);
			$i=0;
			foreach ($relValueArray as $relValue) {
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else {
						$text .= ', ';
					}
				}
				$i+=1;

				$text .= $relValue->getLongWikiText(true);

				$sep = '<!-- -->&nbsp;&nbsp;'; // the comment is needed to prevent MediaWiki from linking URL-strings together with the nbsps!
				foreach ($relValue->getInfolinks() as $link) {
					$text .= $sep . $link->getWikiText();
					$sep = ' &nbsp;&nbsp;'; // allow breaking for longer lists of infolinks
				}
			}
			$text .= '</td></tr>';
		}
/*
		foreach(SMWFactbox::$semdata->getRelations() as $relation) {
			$relationObjectArray = SMWFactbox::$semdata->getRelationObjects($relation);
			//$text .= '   ' . SMWFactbox::$semdata->getSubject()->getPrefixedText() . '&nbsp;';
			$text .= '<tr><td class="smwrelname">[[' . $relation->getPrefixedText() . '|' . preg_replace('/[\s]/','&nbsp;',$relation->getText(),2) . ']]</td><td class="smwrels">';
			// TODO: the preg_replace is a kind of hack to ensure that the left column does ont get too narrow; maybe we can find something nicer later

			$l = count($relationObjectArray);
			$i=0;
			foreach ($relationObjectArray as $relationObject) {
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else {
						$text .= ', ';
					}
				}
				$i+=1;

				$text .= '[[:' . $relationObject->getPrefixedText() . ']]';
				$searchlink = SMWInfolink::newRelationSearchLink('+',$relation->getText(),$relationObject->getPrefixedText());
				$text .= '&nbsp;&nbsp;' . $searchlink->getWikiText();
			}
			$text .= "</td></tr>\n";
		}*/
	}


	/**
	 * This method prints special properties at the bottom of an article.
	 */
	static protected function printSpecialProperties(&$text) {
		if (SMWFactbox::$semdata->hasSpecialProperties()) {
			$text .= ' <tr><th class="spechead"></th><th class="spechead">' . wfMsgForContent('smw_spec_head') . "</th></tr>\n";
		} else {
			return true;
		}

		global $smwgContLang, $wgContLang;
		$specprops = $smwgContLang->getSpecialPropertiesArray();
		foreach(SMWFactbox::$semdata->getSpecialProperties() as $specialProperty) {
			$valueArray = SMWFactbox::$semdata->getSpecialValues($specialProperty);
			if (array_key_exists($specialProperty,$specprops)) { // only print specprops with an official name
				$specialPropertyName = $specprops[$specialProperty];
				foreach ($valueArray as $value) {
					if ($value instanceof SMWDataValue) {
						$vt = $value->getLongWikiText(true);
						if ($specialProperty != SMW_SP_HAS_TYPE) {
							$vn = $wgContLang->getNsText(SMW_NS_ATTRIBUTE);
						} else {
							$vn = $wgContLang->getNsText(SMW_NS_RELATION); //HACK
						}
					} elseif ($value instanceof Title) {
						$vt = '[[' . $value->getPrefixedText() . ']]';
						$vn = $wgContLang->getNsText(SMW_NS_RELATION);
					} else {
						$vt = $value;
						$vn = $wgContLang->getNsText(SMW_NS_ATTRIBUTE);
					}
					$text .= '<tr><td class="smwspecname">[[' . $vn. ':' . $specialPropertyName . '|' . $specialPropertyName . ']]</td><td class="smwspecs">' . $vt . "</td></tr>\n";
				}
			}
		}
	}

//// Methods for writing the content of this object

	/**
	 * This method stores the semantic data, and clears any outdated entries
	 * for the current article.
	 * @TODO: is $title still needed, since we now have SMWFactbox::$title? Could they differ significantly?
	 */
	static function storeData(&$t, $processSemantics) {
		// clear data even if semantics are not processed for this namespace
		// (this setting might have been changed, so that data still exists)
		$title = SMWFactbox::$semdata->getSubject();
		if ($processSemantics) {
			smwfGetStore()->updateData(SMWFactbox::$semdata);
		} else {
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


