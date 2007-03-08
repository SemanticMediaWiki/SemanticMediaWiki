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
 * Class for representing junks of semantic data for one given 
 * article (subject), similar what is typically displayed in the factbox.
 * This is a light-weight data container.
 */
class SMWSemData {
	protected $relobjs = Array(); // text keys and arrays of title objects
	protected $reltitles = Array(); // text keys and title objects
	protected $attribvals = Array(); // text keys and arrays of datavalue objects
	protected $attribtitles = Array(); // text keys and title objects
	protected $specprops = Array(); // integer keys and mixed subarrays
	
	protected $subject;

	public function SMWSemData(Title $subject) {
		$this->subject = $subject;
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$relobjs = Array();
		$reltitles = Array();
		$attribvals = Array();
		$attribtitles = Array();
		$specprops = Array();
	}

//// Attributes

	/**
	 * Get the array of all attributes that have stored values.
	 */
	public function getAttributes() {
		return $this->attribtitles;
	}

	/**
	 * Get the array of all stored values for some attribute.
	 */
	public function getAttributeValues(Title $attribute) {
		if (array_key_exists($attribute->getText(), $this->attribvals)) {
			return $this->attribvals[$attribute->getText()];
		} else {
			return Array();
		}
	}

	/**
	 * Return true if there are any attributes.
	 */
	public function hasAttributes() {
		return (count($this->attribtitles) != 0);
	}

	/**
	 * Store a value for an attribute identified by its title object. Duplicate 
	 * value entries are ignored.
	 */
	public function addAttributeValue(Title $attribute, SMWDataValue $value) {
		if (!array_key_exists($attribute->getText(), $this->attribvals)) {
			$this->attribvals[$attribute->getText()] = Array();
			$this->attribtitles[$attribute->getText()] = $attribute;
		}
		$this->attribvals[$attribute->getText()][$value->getHash()] = $value;
	}

	/**
	 * Store a value for a given attribute identified by its text label (without
	 * namespace prefix). Duplicate value entries are ignored.
	 */
	public function addAttributeTextValue($attributetext, SMWDataValue $value) {
		if (array_key_exists($attributetext, $this->attribtitles)) {
			$attribute = $this->attribtitles[$attributetext];
		} else {
			$attribute = Title::newFromText($attributetext, SMW_NS_ATTRIBUTE);
		}
		$this->addAttributeValue($attribute, $value);
	}

//// Relations

	/**
	 * Get the array of all relations that have stored values.
	 */
	public function getRelations() {
		return $this->reltitles;
	}

	/**
	 * Get the array of all stored objects for some relation.
	 */
	public function getRelationObjects(Title $relation) {
		if (array_key_exists($relation->getText(), $this->relobjs)) {
			return $this->relobjs[$relation->getText()];
		} else {
			return Array();
		}
	}

	/**
	 * Return true if there are any relations.
	 */
	public function hasRelations() {
		return (count($this->reltitles) != 0);
	}

	/**
	 * Store an object for a relation identified by its title. Duplicate 
	 * object entries are ignored.
	 */
	public function addRelationObject(Title $relation, Title $object) {
		if (!array_key_exists($relation->getText(), $this->relobjs)) {
			$this->relobjs[$relation->getText()] = Array();
			$this->reltitles[$relation->getText()] = $relation;
		}
		$this->relobjs[$relation->getText()][$object->getPrefixedText()] = $object;
	}

	/**
	 * Store an object for a given relation identified by its text label (without
	 * namespace prefix). Duplicate value entries are ignored.
	 */
	public function addRelationTextObject($relationtext, Title $object) {
		if (array_key_exists($relationtext, $this->reltitles)) {
			$relation = $this->reltitles[$relationtext];
		} else {
			$relation = Title::newFromText($relationtext, SMW_NS_RELATION);
		}
		$this->addRelationObject($relation, $object);
	}

//// Special properties

	/**
	 * Get the array of all special properties (encoded as integer constants) that 
	 * have stored values.
	 */
	public function getSpecialProperties() {
		return array_keys($this->specprops);
	}

	/**
	 * Get the array of all stored values for some special property (identified
	 * by its integer constant).
	 */
	public function getSpecialValues($special) {
		if (array_key_exists($special, $this->specprops)) {
			return $this->specprops[$special];
		} else {
			return Array();
		}
	}

	/**
	 * Return true if there are any special properties.
	 */
	public function hasSpecialProperties() {
		return (count($this->specprops) != 0);
	}

	/**
	 * Store a value for a special property identified by an integer contant. Duplicate 
	 * value entries are ignored. Values are not type checked, since different special
	 * properties may take different values (Titles, strings, Datavalues).
	 */
	public function addSpecialValue($special, $value) {
		if (!array_key_exists($special, $this->specprops)) {
			$this->specprops[$special] = Array();
		}
		if ($value instanceof SMWDataValue) {
			$this->specprops[$special][$value->getHash()] = $value;
		} elseif ($value instanceof Title) {
			$this->specprops[$special][$value->getPrefixedText()] = $value;
		} else {
			$this->specprops[$special][$value] = $value;
		}
	}

}

/**
 * Static class for representing semantic data, which accepts user 
 * inputs and provides methods for printing and storing its contents.
 * Its main purpose is to provide a persistent storage to keep semantic 
 * data between hooks for parsing and storing.
 */
class SMWSemanticData {

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
		SMWSemanticData::$semdata = new SMWSemData($title);
		SMWSemanticData::$skin = $skin;
	}

	/**
	 * Clear all stored data
	 */
	static function clearStorage() {
		global $smwgStoreActive;
		if ($smwgStoreActive) {
			SMWSemanticData::$semdata->clear();
		}
	}

//// Methods for adding data to the object

	/**
	 * This method adds a new attribute with the given value to the storage.
	 * It returns an array which contains the result of the operation in 
	 * various formats.
	 */
	static function addAttribute($attribute, $value) {
		global $smwgContLang, $smwgStoreActive;
		// See if this attribute is a special one like e.g. "Has unit"
		$attribute = smwfNormalTitleText($attribute); //slightly normalize label
		$specprops = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($attribute, $specprops);

		switch ($special) {
			case false: // normal attribute
				$result = SMWDataValue::newAttributeValue($attribute,SMWSemanticData::$skin,$value);
				if ($smwgStoreActive) {
					SMWSemanticData::$semdata->addAttributeTextValue($attribute,$result);
				}
				return $result;
			case SMW_SP_IMPORTED_FROM: // this requires special handling
				return SMWSemanticData::addImportedDefinition($value);
			default: // generic special attribute
				if ( $special === SMW_SP_SERVICE_LINK ) { // do some custom formatting in this case
					global $wgContLang;
					$v = str_replace(' ', '_', $value); //normalize slightly since messages distinguish '_' and ' '
					$result = SMWDataValue::newSpecialValue($special,SMWSemanticData::$skin,$v);
					$v = $result->getXSDValue(); //possibly further sanitized, so let's be cautious
					$result->setProcessedValues($value,$v); //set user value back to the input version
					$result->setPrintoutString('[[' . $wgContLang->getNsText(NS_MEDIAWIKI) . ':smw_service_' . $v . "|$value]]");
				} else { // standard processing
					$result = SMWDataValue::newSpecialValue($special,SMWSemanticData::$skin,$value);
				}
				if ($smwgStoreActive) {
					SMWSemanticData::$semdata->addSpecialValue($special,$result);
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
		$object = Title::newFromText($target);

		if ($special !== false) {
			$type = SMWTypeHandlerFactory::getSpecialTypeHandler($special);
			if ($type->getID() !=  'error') { //Oops! This is not a relation!
				//Note that this still changes the behaviour, since the [[ ]]
				//are not removed! A cleaner solution would be to print a
				//helpful message into the factbox, based on a new "print value as
				//error" datatype handler. FIXME
				SMWSemanticData::addAttribute($relation, $target);
			} else {
				SMWSemanticData::$semdata->addSpecialValue($special, $object);
			}
		} else {
			SMWSemanticData::$semdata->addRelationTextObject($relation, $object);
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
	static private function addImportedDefinition($value) {
		global $wgContLang, $smwgStoreActive;;

		list($onto_ns,$onto_section) = explode(':',$value,2);
		$msglines = preg_split("([\n][\s]?)",wfMsgForContent("smw_import_$onto_ns")); // get the definition for "$namespace:$section"

		if ( count($msglines) < 2 ) { //error: no elements for this namespace
			$datavalue = SMWDataValue::newTypedValue(new SMWErrorTypeHandler(wfMsgForContent('smw_unknown_importns',$onto_ns)),SMWSemanticData::$skin,$value);
			if (!$smwgStoreActive) { //FIXME: is this "!" correct (also below)
				SMWSemanticData::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM,$datavalue);
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
		$this_ns = SMWSemanticData::$semdata->getSubject()->getNamespace();
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
			if (!$smwgStoreActive) {
				SMWSemanticData::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM,$result);
			}
			return $datavalue;
		}

		// Note: the following just overwrites any existing values for the given
		// special properties, since they can only have one value anyway; this 
		// might hide errors -- should we care?
		$sth = new SMWStringTypeHandler(); // making one is enough ...

		if (!$smwgStoreActive) {
			$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_uri);
			SMWSemanticData::$semdata->addSpecialValue(SMW_SP_EXT_BASEURI,$datavalue);
			$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_ns);
			SMWSemanticData::$semdata->addSpecialValue(SMW_SP_EXT_NSID,$datavalue);
			$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,$onto_section);
			SMWSemanticData::$semdata->addSpecialValue(SMW_SP_EXT_SECTION,$datavalue);
			if (NULL != $datatype) {
				SMWSemanticData::$semdata->addSpecialValue(SMW_SP_HAS_TYPE,$datatype);
			}
		}

		// print the input (this property is not stored, see SMW_Storage.php)
		$datavalue = SMWDataValue::newTypedValue($sth,SMWSemanticData::$skin,"[$onto_uri$onto_section $value]");
		// TODO: Unfortunatelly, the following line can break the tooltip code if $onto_name has markup. -- mak
		// if ('' != $onto_name) $datavalue->setPrintoutString($onto_name, 'onto_name');
		if ('' != $onto_name) $datavalue->setPrintoutString("[$onto_uri$onto_section $value] ($onto_name)");
		if (!$smwgStoreActive) {
			SMWSemanticData::$semdata->addSpecialValue(SMW_SP_IMPORTED_FROM, $datavalue);
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
			if ( (!SMWSemanticData::$semdata->hasRelations()) && (!SMWSemanticData::$semdata->hasAttributes()) && (!SMWSemanticData::$semdata->hasSpecialProperties()) ) return true;
		}

		$rdflink = new SMWInfolink(
		   $wgServer . SMWSemanticData::$skin->makeSpecialUrl('ExportRDF') . '/' . str_replace('%2F', '/', urlencode(SMWSemanticData::$semdata->getSubject()->getPrefixedText())),
		   wfMsgForContent('smw_viewasrdf'),'rdflink');
		// The "\n" is to ensure that lists on the end of articles are terminated
		// before the div starts. It would of course be much cleaner to print the
		// factbox in another way, similar to the way that categories are printed 
		// now. However, this would require more patching of MediaWiki code ...
		$text .= "\n" . '<div class="smwfact">' .
		         '<span class="smwfactboxhead">' . wfMsgForContent('smw_factbox_head', SMWSemanticData::$semdata->getSubject()->getText()) . '</span>' .
		         '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
		         '<table style="clear: both; width: 100%">' . "\n";
		SMWSemanticData::printRelations($text);
		SMWSemanticData::printAttributes($text);
		SMWSemanticData::printSpecialProperties($text);
		$text .= '</table></div>';
	}

	/**
	 * This method prints attribute values at the bottom of an article.
	 */
	static protected function printAttributes(&$text) {
		if (!SMWSemanticData::$semdata->hasAttributes()) {
			return;
		}

		$text .= ' <tr><th class="relhead"></th><th class="atthead">' . wfMsgForContent('smw_att_head') . "</th></tr>\n";

		foreach(SMWSemanticData::$semdata->getAttributes() as $attribute) {
			$attributeValueArray = SMWSemanticData::$semdata->getAttributeValues($attribute);
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

				$text .= $attributeValue->getValueDescription();

				$sep = '<!-- -->&nbsp;&nbsp;'; // the comment is needed to prevent MediaWiki from linking URL-strings together with the nbsps! This only works with template support enabled.
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
		if(!SMWSemanticData::$semdata->hasRelations()) { return true; }

		$text .= ' <tr><th class="relhead"></th><th class="relhead">' . wfMsgForContent('smw_rel_head') . "</th></tr>\n";
		
		foreach(SMWSemanticData::$semdata->getRelations() as $relation) {
			$relationObjectArray = SMWSemanticData::$semdata->getRelationObjects($relation);
			//$text .= '   ' . SMWSemanticData::$semdata->getSubject()->getPrefixedText() . '&nbsp;';
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
				$searchlink = new SMWInfolink(
				         SMWInfolink::makeRelationSearchURL($relation->getText(), $relationObject->getPrefixedText(), SMWSemanticData::$skin),
				         '+','smwsearch');
				$text .= '&nbsp;&nbsp;' . $searchlink->getWikiText();
			}
			$text .= "</td></tr>\n";
		}
	}


	/**
	 * This method prints special properties at the bottom of an article.
	 */
	static protected function printSpecialProperties(&$text) {
		if (SMWSemanticData::$semdata->hasSpecialProperties()) {
			$text .= ' <tr><th class="spechead"></th><th class="spechead">' . wfMsgForContent('smw_spec_head') . "</th></tr>\n";
		} else {
			return true; 
		}

		global $smwgContLang, $wgContLang;
		$specprops = $smwgContLang->getSpecialPropertiesArray();
		foreach(SMWSemanticData::$semdata->getSpecialProperties() as $specialProperty) {
			$valueArray = SMWSemanticData::$semdata->getSpecialValues($specialProperty);
			if (array_key_exists($specialProperty,$specprops)) { // only print specprops with an official name
				$specialPropertyName = $specprops[$specialProperty];
				foreach ($valueArray as $value) {
					if ($value instanceof SMWDataValue) {
						$vt = $value->getValueDescription();
						$vn = $wgContLang->getNsText(SMW_NS_ATTRIBUTE);
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
	 * @TODO: is $title still needed, since we now have SMWSemanticData::$title? Could they differ significantly?
	 */
	static function storeData(&$t, $processSemantics) {
		// clear data even if semantics are not processed for this namespace
		// (this setting might have been changed, so that data still exists)
		$title = SMWSemanticData::$semdata->getSubject();
		if ($processSemantics) {
			smwfGetStore()->updateData(SMWSemanticData::$semdata);
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

?>