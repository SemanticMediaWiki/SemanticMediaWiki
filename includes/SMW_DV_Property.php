<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Objects of this class represent properties in SMW.
 *
 * This class represents both normal (user-defined) properties and
 * predefined ("special") properties. The internal value ("XSD value")
 * is either the page DB key (normal properties) or the internal
 * property identifier (predefined properties).
 *
 * Internally, the fields of the parent class SMWWikiPageValue are used
 * to store the data that is displayed, but not necessarily to represent
 * the internal id (if the property is pre-defined). Hence, most output
 * methods can be used, but input methods and internal data outputs must
 * be re-implemented/extended.
 *
 * @note The internal IDs provide a way to keep the internal storage
 * language independent: pre-defined properties will have different names
 * for different languages, yet they can use a single id that can be
 * addressed in code and stored on disk. Thus internal code does not need
 * to take language settings into account.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWPropertyValue extends SMWWikiPageValue {

	/// This value contains the property's DB key (normal property) or internal id (predefined properties).
	protected $m_propertyid;

	public function __construct($typeid) {
		parent::__construct($typeid);
		$this->m_fixNamespace = SMW_NS_PROPERTY;
	}

	/**
	 * Extended parsing function to first check whether value refers to pre-defined
	 * property, resolve aliases, and set internal property id accordingly.
	 */
	protected function parseUserValue($value) {
		global $smwgContLang;
		$value = smwfNormalTitleText(ltrim(rtrim($value,' ]'),' [')); //slightly normalise label
		$special = $smwgContLang->findSpecialPropertyID($value);
		if ($special !== false) {
			if ($this->m_caption === false) {
				$this->m_caption = $value;
			}
			$value = $smwgContLang->findSpecialPropertyLabel($special);
			$this->m_propertyid = $special;
		} else {
			$this->m_propertyid = false;
		}
		parent::parseUserValue($value);
		$this->m_propertyid = ($special !== false)?$special:$this->m_dbkeyform;
	}

	/**
	 * Extended parsing function to first check whether value is the id of a
	 * pre-defined property, to resolve property names and aliases, and to set
	 * internal property id accordingly.
	 */
	protected function unstub() {
		global $smwgContLang;
		if (is_array($this->m_stubdata)) {
			$this->m_propertyid = false;
			if ($this->m_stubdata[0][0] == '_') { // internal id, consume
				$special = $this->m_stubdata[0];
			} else { // possibly name of special property
				$special = $smwgContLang->findSpecialPropertyID(str_replace('_',' ',$this->m_stubdata[0]));
			}
			if ($special !== false) {
				$this->m_stubdata[0] = $smwgContLang->findSpecialPropertyLabel($this->m_propertyid);
			}
			parent::unstub(); // note that this will destroy stubdata array
			$this->m_propertyid = ($special !== false)?$special:$this->m_dbkeyform;
		}
	}

	/**
	 * Return internal property id as the main way of storing property references.
	 */
	public function getXSDValue() {
		$this->unstub();
		return $this->m_propertyid;
	}

}
