<?php
/**
 * The class in this file manages relations, attributes, and 
 * special properties that are associated with a certain subject (article).
 * It is used as a container for chunks of subject-centred data.
 *
 * @author Markus Krötzsch
 */

require_once('SMW_DataValue.php');

/**
 * Class for representing junks of semantic data for one given 
 * article (subject), similar what is typically displayed in the factbox.
 * This is a light-weight data container.
 */
class SMWSemanticData {
	protected $relobjs = Array(); // text keys and arrays of title objects
	protected $reltitles = Array(); // text keys and wikipage values, TODO: join with attributes when namespaces were merged
	protected $attribvals = Array(); // text keys and arrays of datavalue objects
	protected $attribtitles = Array(); // text keys and title objects
	protected $specprops = Array(); // integer keys and mixed subarrays
	
	protected $subject;

	public function SMWSemanticData(Title $subject) {
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
		ksort($this->attribtitles,SORT_STRING);
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
			if ($attribute === NULL) { // error, maybe illegal title text
				return;
			}
		}
		$this->addAttributeValue($attribute, $value);
	}

//// Relations

	/**
	 * Get the array of all relations that have stored values.
	 */
	public function getRelations() {
		ksort($this->reltitles,SORT_STRING);
		return $this->reltitles;
	}

	/**
	 * Get the array of all stored objects for some relation.
	 */
	public function getRelationValues(Title $relation) {
		if (array_key_exists($relation->getText(), $this->relobjs)) {
			return $this->relobjs[$relation->getText()];
		} else {
			return array();
		}
	}

	/**
	 * Return true if there are any relations.
	 */
	public function hasRelations() {
		return (count($this->reltitles) != 0);
	}

	/**
	 * Store a value for a relation identified by its title. Duplicate 
	 * object entries are ignored.
	 */
	public function addRelationValue(Title $relation, SMWDataValue $value) {
		if (!array_key_exists($relation->getText(), $this->relobjs)) {
			$this->relobjs[$relation->getText()] = Array();
			$this->reltitles[$relation->getText()] = $relation;
		}
		$this->relobjs[$relation->getText()][$value->getHash()] = $value;
	}

	/**
	 * Store a value for a given relation identified by its text label (without
	 * namespace prefix). Duplicate value entries are ignored.
	 */
	public function addRelationTextvalue($relationtext, SMWDataValue $value) {
		if (array_key_exists($relationtext, $this->reltitles)) {
			$relation = $this->reltitles[$relationtext];
		} else {
			$relation = Title::newFromText($relationtext, SMW_NS_RELATION);
			if ($relation === NULL) { // error, maybe illegal title text
				return;
			}
		}
		$this->addRelationValue($relation, $value);
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
			if ($relation === NULL) { // error, maybe illegal title text
				return;
			}
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

