<?php
/**
 * The class in this file manages (special) properties that are
 * associated with a certain subject (article). It is used as a
 * container for chunks of subject-centred data.
 * @file
 * @ingroup SMW
 * @author Markus Krötzsch
 */

/**
 * Class for representing chunks of semantic data for one given 
 * article (subject), similar what is typically displayed in the factbox.
 * This is a light-weight data container.
 * @ingroup SMW
 */
class SMWSemanticData {
	/// Text keys and arrays of datavalue objects.
	protected $propvals = array();
	/// Text keys and title objects.
	protected $properties = array();
	/// Boolean, stating whether the container holds any normal properties.
	protected $hasvisibleprops = false;
	/// Boolean, stating whether the container holds any displayable special properties (some are internal only without a display name).
	protected $hasvisiblespecs = false;
	/// Boolean, stating whether this is a stub object. Stubbing might happen on serialisation to safe DB space
	public $stubobject = true;
	/**
	 *  Boolean, stating whether repeated values should be avoided. Not needing duplicte elimination
	 *  (e.g. when loading from store) can safe much time, since objects can remain stubs until someone
	 *  really acesses their value.
	 */
	protected $m_noduplicates;
	/// Cache for the local version of "Property:"
	static protected $m_propertyprefix = false;

	/// SMWWikiPageValue object that is the subject of this container.
	protected $subject;

	public function __construct(SMWWikiPageValue $subject, $noduplicates = true) {
		$this->subject = $subject;
		$this->m_noduplicates = $noduplicates;
		$this->stubobject = false;
	}

	/**
	 * This object is added to the parser output of MediaWiki, but it is not useful to have all its data as part of the parser cache
	 * since the data is already stored in more accessible format in SMW. Hence this implementation of __sleep() makes sure only the 
	 * subject is serialised, yielding a minimal stub data container after unserialisation. This is a little safer than serialising
	 * nothing: if, for any reason, SMW should ever access an unserialised parser output, then the Semdata container will at least
	 * look as if properly initialised (though empty).
	 * @note It might be even better to have other members with stub object data that is used for serializing, thus using much less data.
	 */
	public function __sleep() {
		return array('subject');
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 * @return SMWWikiPageValue subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Get the array of all properties that have stored values.
	 */
	public function getProperties() {
		ksort($this->properties,SORT_STRING);
		return $this->properties;
	}

	/**
	 * Get the array of all stored values for some property.
	 */
	public function getPropertyValues(SMWPropertyValue $property) {
		if (array_key_exists($property->getXSDValue(), $this->propvals)) {
			return $this->propvals[$property->getXSDValue()];
		} else {
			return array();
		}
	}

	/**
	 * Return true if there are any visible properties.
	 */
	public function hasVisibleProperties() {
		return $this->hasvisibleprops;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 */
	public function hasVisibleSpecialProperties() {
		return $this->hasvisiblespecs;
	}

	/**
	 * Store a value for an property identified by its title object. Duplicate 
	 * value entries are ignored.
	 * @note Attention: there is no check whether the type of the given datavalue agrees
	 * with what SMWDataValueFactory is producing (based on predefined property records and
	 * the current DB content). Always use SMWDataValueFactory to produce fitting values!
	 */
	public function addPropertyObjectValue(SMWPropertyValue $property, SMWDataValue $value) {
		if (!$property->isValid()) return; // nothing we can do
		if (!array_key_exists($property->getXSDValue(), $this->propvals)) {
			$this->propvals[$property->getXSDValue()] = array();
			$this->properties[$property->getXSDValue()] = $property;
		}
		if ($this->m_noduplicates) {
			$this->propvals[$property->getXSDValue()][$value->getHash()] = $value;
		} else {
			$this->propvals[$property->getXSDValue()][] = $value;
		}
		if (!$property->isUserDefined()) {
			if ($property->isVisible()) {
				 $this->hasvisiblespecs = true;
				 $this->hasvisibleprops = true;
			}
		} else {
			$this->hasvisibleprops = true;
		}
	}

	/**
	 * Store a value for a given property identified by its text label (without
	 * namespace prefix). Duplicate value entries are ignored.
	 */
	public function addPropertyValue($propertyname, SMWDataValue $value) {
		$propertykey = smwfNormalTitleDBKey($propertyname);
		if (array_key_exists($propertykey, $this->properties)) {
			$property = $this->properties[$propertykey];
		} else {
			if (SMWSemanticData::$m_propertyprefix == false) {
				global $wgContLang;
				SMWSemanticData::$m_propertyprefix = $wgContLang->getNsText(SMW_NS_PROPERTY) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]
			$property = SMWPropertyValue::makeUserProperty(SMWSemanticData::$m_propertyprefix . $propertyname);
			if (!$property->isValid()) { // error, maybe illegal title text
				return;
			}
		}
		$this->addPropertyObjectValue($property, $value);
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$this->propvals = array();
		$this->properties = array();
		$this->hasvisibleprops = false;
		$this->hasvisiblespecs = false;
	}

}

