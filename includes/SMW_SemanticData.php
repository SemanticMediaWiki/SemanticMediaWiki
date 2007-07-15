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

//// Properties

	/**
	 * Get the array of all properties that have stored values.
	 */
	public function getProperties() {
		ksort($this->attribtitles,SORT_STRING);
		return $this->attribtitles;
	}

	/**
	 * Get the array of all stored values for some property.
	 */
	public function getPropertyValues(Title $property) {
		if (array_key_exists($property->getText(), $this->attribvals)) {
			return $this->attribvals[$property->getText()];
		} else {
			return array();
		}
	}

	/**
	 * Return true if there are any properties.
	 */
	public function hasProperties() {
		return (count($this->attribtitles) != 0);
	}

	/**
	 * Store a value for an property identified by its title object. Duplicate 
	 * value entries are ignored.
	 */
	public function addPropertyObjectValue(Title $property, SMWDataValue $value) {
		if (!array_key_exists($property->getText(), $this->attribvals)) {
			$this->attribvals[$property->getText()] = Array();
			$this->attribtitles[$property->getText()] = $property;
		}
		$this->attribvals[$property->getText()][$value->getHash()] = $value;
	}

	/**
	 * Store a value for a given property identified by its text label (without
	 * namespace prefix). Duplicate value entries are ignored.
	 */
	public function addPropertyValue($propertyname, SMWDataValue $value) {
		if (array_key_exists($propertyname, $this->attribtitles)) {
			$property = $this->attribtitles[$propertyname];
		} else {
			$property = Title::newFromText($propertyname, SMW_NS_PROPERTY);
			if ($property === NULL) { // error, maybe illegal title text
				return;
			}
		}
		$this->addPropertyObjectValue($property, $value);
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


//// Attributes (deprecated)

	/**
	 * Get the array of all attributes that have stored values.
	 */
	public function getAttributes() {
		trigger_error("Function getAttributes is deprecated. Use new property methods.", E_USER_NOTICE);
		return $this->getProperties();
	}

	/**
	 * Get the array of all stored values for some attribute.
	 */
	public function getAttributeValues(Title $attribute) {
		trigger_error("Function getAttributeValues is deprecated. Use new property methods.", E_USER_NOTICE);
		return $this->getAttributeValues($attribute);
	}

	/**
	 * Return true if there are any attributes.
	 */
	public function hasAttributes() {
		trigger_error("Function hasAttributes is deprecated. Use new property methods.", E_USER_NOTICE);
		return $this->hasProperties();
	}

//// Relations (deprecated)

	/**
	 * Get the array of all relations that have stored values.
	 */
	public function getRelations() {
		trigger_error("Function getRelations is deprecated. Use new property methods.", E_USER_NOTICE);
		return array();
	}

	/**
	 * Get the array of all stored objects for some relation.
	 */
	public function getRelationValues(Title $relation) {
		trigger_error("Function getRelationValues is deprecated. Use new property methods.", E_USER_NOTICE);
		return array();
	}

	/**
	 * Return true if there are any relations.
	 */
	public function hasRelations() {
		trigger_error("Function hasRelations is deprecated. Use new property methods.", E_USER_NOTICE);
		return false;
	}

}

