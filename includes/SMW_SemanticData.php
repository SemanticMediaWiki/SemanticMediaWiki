<?php
/**
 * The class in this file manages link types, attributes,
 * and other types of semantic data during parsing and
 * storing.
 *
 * @author Markus Krötzsch
 */

require_once('SMW_DataValue.php');
require_once('SMW_Storage.php');

/**
 * Static class for representing semantic data, which accepts user 
 * inputs and provides methods for printing and storing its contents.
 * Its main purpose is to provide a persistent storage to keep semantic 
 * data between hooks for parsing and storing.
 */
class SMWSemanticData {
	/**#@+
	 * @access private
	 */
	/**
	 * Attribute store, elements are arrays indexed by attribute
	 * names. Each entry in these arrays is another array that contains 
	 * information for specified values, each given as an SMWDataValue.
	 */
	static private $attribArray = Array();
	/**
	 * Relation store, elements are arrays indexed by relation
	 * names. Each entry in these arrays contains just another
	 * array of object stings that were used with this relation.
	 */
	static private $relArray = Array();
	/**
	 * Like $attribArray, but for special property attributes,
	 * indexed by property identifier constants (see SMW_Settings.php).
	 */
	static private $specaArray = Array();
	/**
	 * Like $relArray, but for special property attributes,
	 * indexed by property identifier constants (see SMW_Settings.php).
	 */
	static private $specrArray = Array();
	/**
	 * The skin that is to be used for output functions.
	 */
	static private $skin;
	/**
	 * The title object of the article that is processed.
	 */
	static private $title;
	/**#@-*/

	/**
	 * Initialisation method. Must be called before anything else happens.
	 */
	static function initStorage($title, $skin) {
		SMWSemanticData::$attribArray = Array();
		SMWSemanticData::$relArray = Array();
		SMWSemanticData::$specaArray = Array();
		SMWSemanticData::$specrArray = Array();
		SMWSemanticData::$title = $title;
		SMWSemanticData::$skin = $skin;
	}

	/*********************************************************************/
	/* Methods for adding data to the object                             */
	/*********************************************************************/

	/**
	 * This method adds a new attribute with the given value to the storage.
	 * It returns an array which contains the result of the operation in 
	 * various formats.
	 */
	static function addAttribute($attribute, $value) {
		// See if this attribute is a special one like e.g. "Has type"
		global $smwgContLang;
		$attribute = ucfirst($attribute); //slightly normalize label
		$specprops = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($attribute, $specprops);

		switch ($special) {
			case NULL: // normal attribute
				if(!array_key_exists($attribute,SMWSemanticData::$attribArray)) {
					SMWSemanticData::$attribArray[$attribute] = Array();
				}
				$result = SMWDataValue::newAttributeValue($attribute,SMWSemanticData::$skin,$value);
				SMWSemanticData::$attribArray[$attribute][$result->getHash()] = $result;
				return $result;
			case SMW_SP_IMPORTED_FROM: // this requires special handling
				return SMWSemanticData::addImportedDefinition($value);
			default: // generic special attribute
				if(!array_key_exists($special,SMWSemanticData::$specaArray)) {
					SMWSemanticData::$specaArray[$special] = Array();
				}
				$result = SMWDataValue::newSpecialValue($special,SMWSemanticData::$skin,$value);
				SMWSemanticData::$specaArray[$special][$result->getHash()] = $result;
				return $result;
		}
	}


	/**
	 * This method adds a new relation with the given target to the storage.
	 */
	static function addRelation($relation, $target) {
		global $smwgContLang;
		$relation = ucfirst($relation);
		$srels = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($relation, $srels);

		if ($special!=NULL) { //requires PHP >=4.2.0
			$type = SMWTypeHandlerFactory::getSpecialTypeHandler($special);
			if ($type->getID() !=  'error') { //Oops! This is not a relation!
				//Note that this still changes the behaviour, since the [[ ]]
				//are not removed! A cleaner solution would be to print a
				//helpful message into the factbox, based on a new "print value as
				//error" datatype handler.
				SMWSemanticData::addAttribute($relation, $target);
			} else {
				// Create a new array for this specific semantic relation
				if(!array_key_exists($special,SMWSemanticData::$specrArray)) {
					SMWSemanticData::$specrArray[$special] = Array();
				}
				// Store relation and target, if it does not exist yet
				if(!in_array($target, SMWSemanticData::$specrArray[$special])) {
					SMWSemanticData::$specrArray[$special][] = $target;
				}
			}
		} else {
			// Create a new array for this specific semantic relation
			if(!array_key_exists($relation,SMWSemanticData::$relArray)) {
				SMWSemanticData::$relArray[$relation] = Array();
			}
			// Store relation and target, if it does not exist yet
			if(!in_array($target, SMWSemanticData::$relArray[$relation])) {
				SMWSemanticData::$relArray[$relation][] = $target;
			}
		}
		return;
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
	static private function addImportedDefinition($value) {
		global $wgContLang;

		list($onto_ns,$onto_section) = explode(':',$value,2);
		$msglines = preg_split("([\n][\s]?)",wfMsgForContent("smw_import_$onto_ns")); // get the definition for "$namespace:$section"

		if ( count($msglines) < 2 ) { //error: no elements for this namespace
			$datavalue = SMWDataValue::newTypedValue(new SMWErrorTypeHandler(wfMsgForContent('smw_unknown_importns',$onto_ns)),SMWSemanticData::$skin,$value);
			SMWSemanticData::$specaArray[SMW_SP_IMPORTED_FROM] = array($datavalue);
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
						$datatype = $typestring;
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
		$this_ns = SMWSemanticData::$title->getNamespace();
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
			$datavalue = SMWDataValue::newTypedValue(new SMWErrorTypeHandler($error),SMWSemanticData::$skin,$value);
			SMWSemanticData::$specaArray[SMW_SP_IMPORTED_FROM] = array ($datavalue);
			return $datavalue;
		}

		// Note: the following just overwrites any existing values for the given
		// special properties, since they can only have one value anyway; this 
		// might hide errors -- should we care?
		$sth = new SMWStringTypeHandler(); // making one is enough ...
		$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_uri);
		SMWSemanticData::$specaArray[SMW_SP_EXT_BASEURI] = array ($datavalue);
		$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_ns);
		SMWSemanticData::$specaArray[SMW_SP_EXT_NSID] = array ($datavalue);
		$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_section);
		SMWSemanticData::$specaArray[SMW_SP_EXT_SECTION] = array ($datavalue);
		if (NULL != $datatype) SMWSemanticData::$specrArray[SMW_SP_HAS_TYPE][] = $datatype;

		// print the input (this property is not stored, see SMW_Storage.php)
		$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,"[$onto_uri$onto_section $value]");
		// TODO: Unfortunatelly, the following line can break the tooltip code if $onto_name has markup. -- mak
		// if ('' != $onto_name) $datavalue->setPrintoutString($onto_name, 'onto_name');
		if ('' != $onto_name) $datavalue->setPrintoutString("[$onto_uri$onto_section $value] ($onto_name)");
		SMWSemanticData::$specaArray[SMW_SP_IMPORTED_FROM] = array($datavalue);
		return $datavalue;
	}

	/*********************************************************************/
	/* Methods for printing the content of this object into an factbox   */
	/*********************************************************************/

	/**
	 * This method prints semantic data at the bottom of an article.
	 * @access public
	 */
	static function printFactbox(&$text) {
		global $wgContLang, $wgServer, $smwgShowFactbox;
		switch ($smwgShowFactbox) {
		case SMW_FACTBOX_HIDDEN: return true;
		case SMW_FACTBOX_NONEMPTY:
			$boxSize = count(SMWSemanticData::$attribArray) + count(SMWSemanticData::$relArray) + count(SMWSemanticData::$specaArray) + count(SMWSemanticData::$specrArray);
			if ($boxSize == 0) return true;
		}

		$rdflink = new SMWInfolink(
		   $wgServer . SMWSemanticData::$skin->makeSpecialUrl('ExportRDF') . '/' . str_replace('%2F', '/', urlencode(SMWSemanticData::$title->getPrefixedText())),
		   wfMsgForContent('smw_viewasrdf'),'rdflink');
		// The "\n" is to ensure that lists on the end of articles are terminated
		// before the div starts. It would of course be much cleaner to print the
		// factbox in another way, similar to the way that categories are printed 
		// now. However, this would require more patching of MediaWiki code ...
		$text .= "\n" . '<div class="smwfact">' .
		         '<span class="smwfactboxhead" style="float:left">' . wfMsgForContent('smw_factbox_head', SMWSemanticData::$title->getText()) . '</span>' .
		         '<span class="smwrdflink" style="float:right">' . $rdflink->getWikiText() . '</span>' .
		         '<table style="clear: both; width: 100%">' . "\n";
		SMWSemanticData::printRelations($text);
		SMWSemanticData::printAttributes($text);
		SMWSemanticData::printSpecialProperties($text);
		$text .= '</table></div>';

		return true;
	}

	/**
	 * This method prints attribute values at the bottom of an article.
	 * @access private
	 */
	static private function printAttributes(&$text) {
		if(count(SMWSemanticData::$attribArray) <= 0) { return true; }

		global $wgContLang;
		$text .= ' <tr><th class="relhead"></th><th class="atthead">' . wfMsgForContent('smw_att_head') . "</th></tr>\n";

		foreach(SMWSemanticData::$attribArray as $attribute => $attributeValueArray)
		{
			$text .= '<tr><td class="smwattname">';
			$text .= '   [[' . $wgContLang->getNsText(SMW_NS_ATTRIBUTE). ':' . $attribute . '|' . preg_replace('/[\s]/','&nbsp;',$attribute,2) . ']] </td><td class="smwatts">';
			// TODO: the preg_replace is a kind of hack to ensure that the left column does ont get too narrow; maybe we can find something nicer later

			$l = count($attributeValueArray);
			$i=0;
			foreach ($attributeValueArray as $attributeValue)
			{
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else {
						$text .= ', ';
					}
				}
				$i+=1;

				$text .= $attributeValue->getValueDescription();

				$sep = '<!-- -->&nbsp;&nbsp;'; // the comment is needed to prevent MediaWiki from linking URL-strings together with the nbsps! This only works with template support enabled.
				foreach ($attributeValue->getInfolinks() as $link) {
					$text .= $sep . $link->getWikiText();
					$sep = ' &nbsp;&nbsp;'; // allow breaking for longer lists of infolinks
				}
			}
			$text .= '</td></tr>';
		}

		return true;
	}

	/**
	 * This method prints semantic relations at the bottom of an article.
	 * @access private
	 */
	static private function printRelations(&$text) {
		//@ TODO: Performance: remember $NS_RELATION value once, 
		//  outside this loop; also in other print loops.
		if(count(SMWSemanticData::$relArray) <= 0) { return true; }

		global $wgContLang;
		$text .= ' <tr><th class="relhead"></th><th class="relhead">' . wfMsgForContent('smw_rel_head') . "</th></tr>\n";

		foreach(SMWSemanticData::$relArray as $relation => $relationObjectArray) {
			//$text .= '   ' . SMWSemanticData::$title->getPrefixedText() . '&nbsp;';
			$text .= '<tr><td class="smwrelname">[[' . $wgContLang->getNsText(SMW_NS_RELATION). ':' . $relation . '|' . preg_replace('/[\s]/','&nbsp;',$relation,2) . ']]</td><td class="smwrels">';
			// TODO: the preg_replace is a kind of hack to ensure that the left column does ont get too narrow; maybe we can find something nicer later

			$l = count($relationObjectArray);
			for ($i=0; $i<$l; $i++) {
				if ($i!=0) {
					if ($i>$l-2) {
						$text .= wfMsgForContent('smw_finallistconjunct') . ' ';
					} else { 
						$text .= ', ';
					}
				}

				$relationObject = $relationObjectArray[$i];
				$text .= '[[:' . $relationObject . ']]';
				$searchlink = new SMWInfolink(
				         SMWInfolink::makeRelationSearchURL($relation, $relationObject, SMWSemanticData::$skin),
				         '+','smwsearch');
				$text .= '&nbsp;&nbsp;' . $searchlink->getWikiText();
			}
			$text .= "</td></tr>\n";
		}
		//$text .= " </p><hr/>\n";

		return true;
	}


	/**
	 * This method prints special properties at the bottom of an article.
	 * @access private
	 */
	static private function printSpecialProperties(&$text) {
		global $wgContLang, $smwgContLang;

		if ((count(SMWSemanticData::$specaArray) > 0)||(count(SMWSemanticData::$specrArray) > 0)) {
			$text .= ' <tr><th class="spechead"></th><th class="spechead">' . wfMsgForContent('smw_spec_head') . "</th></tr>\n";
		} else { return true; }

		$specprops = $smwgContLang->getSpecialPropertiesArray();
		$titletext = SMWSemanticData::$title->getPrefixedText();

		if(count(SMWSemanticData::$specaArray) > 0) {
			foreach(SMWSemanticData::$specaArray as $specialProperty => $valueArray) {
				if (array_key_exists($specialProperty,$specprops)) { // only print specprops with an official name
					$specialPropertyName = $specprops[$specialProperty];
					foreach ($valueArray as $value) {
						$text .= '<tr><td class="smwspecname">[[' . $wgContLang->getNsText(SMW_NS_ATTRIBUTE). ':' . $specialPropertyName . '|' . $specialPropertyName . ']]</td><td class="smwspecs">';
						$text .= $value->getValueDescription();
						$text .= "</td></tr>\n";
					}
				}
			}
		}

		if(count(SMWSemanticData::$specrArray) > 0) {
			foreach(SMWSemanticData::$specrArray as $specialProperty => $valueArray)
			{
				$specialPropertyName = $specprops[$specialProperty];
				foreach ($valueArray as $value) {
					$text .= '<tr><td class="smwspecname">[[' . $wgContLang->getNsText(SMW_NS_RELATION). ':' . $specialPropertyName . '|' . $specialPropertyName . ']]</td><td class="smwspecs">';
					$text .= '[[' . $value . ']]';
					$text .= "</td></tr>\n";
				}

			}
		}

		return true;
	}

	/*********************************************************************/
	/* Methods for storing the content of this object                    */
	/*********************************************************************/

	/**
	 * This method stores the semantic data, and clears any outdated entries
	 * for the current article.
	 * @access public
	 *
	 * @TODO: is $title still needed, since we now have SMWSemanticData::$title? Could they differ significantly?
	 */
	static function storeData(&$title, $processSemantics) {
		// clear data even if semantics are not processed for this namespace
		// (this setting might have been changed, so that data still exists)
		SMWSemanticData::clearData($title);
		if ($processSemantics) {
			SMWSemanticData::storeAttributes($title);
			SMWSemanticData::storeRelations($title);
			SMWSemanticData::storeSpecialProperties($title);
		}
		return true;
	}

	/**
	 * Delete semantic data currently associated with some article.
	 * @access private
	 */
	static private function clearData($s_title) {
		smwfDeleteRelations($s_title);
		smwfDeleteAttributes($s_title);
		smwfDeleteSpecialProperties($s_title);
	}

	/**
	 * Method for storing attributes.
	 * @access private
	 */
	static private function storeAttributes($s_title) {
		if(count(SMWSemanticData::$attribArray) <= 0) {
			return true;
		}

		foreach(SMWSemanticData::$attribArray as $attribute => $attributeValueArray) {
			$a_title = Title::newFromText($attribute,SMW_NS_ATTRIBUTE);
			if ($a_title !== NULL) {
				foreach($attributeValueArray as $value) {
					// DEBUG echo "in storeAttributes, considering $value, getXSDValue=" . $value->getXSDValue() . "<br />\n" ;
					if ($value->getXSDValue()!==false) {
						smwfStoreAttribute($s_title, $a_title, $value->getUnit(), $value->getTypeID(), $value->getXSDValue(), $value->getNumericValue());
					}
				}
			}
		}
		return true;
	}

	/**
	 * Method for storing semantic relations.
	 * @access private
	 */
	static private function storeRelations($s_title) {
		if(count(SMWSemanticData::$relArray) <= 0) {
			return true;
		}

		foreach(SMWSemanticData::$relArray as $semanticRelation => $relationObjectArray)
		{
			$r_title = Title::newFromText($semanticRelation,SMW_NS_RELATION);
			if ($r_title !== NULL) {
				foreach($relationObjectArray as $linkTarget)
				{
					$o_title = Title::newFromText($linkTarget);
					if ( $o_title !== NULL ) {
						smwfStoreRelation($s_title, $r_title, $o_title);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Method for storing special properties.
	 * @access private
	 */
	static private function storeSpecialProperties($s_title) {
		if (count(SMWSemanticData::$specrArray) > 0) {
			foreach(SMWSemanticData::$specrArray as $specialProperty => $valueArray)
			{
				foreach($valueArray as $value)
				{
					smwfStoreSpecialProperty($s_title, $specialProperty, $value);
				}
			}
		}

		if (count(SMWSemanticData::$specaArray) > 0) {
			foreach(SMWSemanticData::$specaArray as $specialProperty => $valueArray) {
				foreach($valueArray as $value) {
					if ($value->getXSDValue()!==false) {
						smwfStoreSpecialProperty($s_title, $specialProperty, $value->getXSDValue());
					}
				}
			}
		}
		return true;
	}

}

?>