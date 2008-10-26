<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Objects of this class represent properties in SMW.
 *
 * This class represents both normal (user-defined) properties and
 * predefined ("special") properties. Predefined properties may still
 * have a standard label (and associated wiki article) and they will
 * behave just like user-defined properties in most cases (e.g. when
 * asking for a printout text, a link to the according page is produced).
 * It is possible that predefined properties have no visible label at all,
 * if they are used only internally and never specified by or shown to
 * the user. Those will use their internal ID as "XSD value", and
 * empty texts for most printouts. All other proeprties use their
 * canonical DB key as "XSD value" (even if they are predefined and 
 * have an id). Functions are provided to check whether a property
 * is visible or user-defined, and to get the internal ID, if any.
 *
 * @note This datavalue is used only for representing properties and,
 * possibly objects/values, but never for subjects (pages as such). Hence
 * it does not rpvide a complete Title-like interface, or support for
 * things like sortkey.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWPropertyValue extends SMWDataValue {

	/** Array for assigning types to predefined properties. Each
	 * property is associated with an array with the following
	 * elements:
	 *
	 * * ID of datatype to be used for this property
	 *
	 * * Boolean, stating if this property is shown in Factbox, Browse, and similar interfaces;
	 *   (note that this is only relevant if the property can be displayed at all, i.e. has an
	 *   translated label in the given language; we still set invisible properties to false here)
	 */
	static private $m_propertytypes = array(
		'_TYPE'  =>  array('__typ',true),
		'_URI'   =>  array('__spu',true),
		'_INST'  =>  array('__sin',false),
		'_UNIT'  =>  array('__sps',true),
		'_IMPO'  =>  array('__imp',true),
		'_CONV'  =>  array('__sps',true),
		'_SERV'  =>  array('__sps',true),
		'_PVAL'  =>  array('__sps',true),
		'_REDI'  =>  array('__red',true),
		'_SUBP'  =>  array('__sup',true),
		'_SUBC'  =>  array('__suc',false),
		'_CONC'  =>  array('__con',false),
		'_MDAT'  =>  array('_dat',false)
	);

	/// If the property is predefined, its internal key is stored here. Otherwise FALSE.
	protected $m_propertyid;
	/// If the property is associated with a wikipage, it is stored here. Otherwise NULL.
	protected $m_wikipage;

	/**
	 * Static function for creating a new property object from a
	 * propertyname (string) as a user might enter it.
	 * @note The resulting property object might be invalid if
	 * the provided name is not allowed. An object is returned
	 * in any case.
	 */
	static public function makeUserProperty($propertyname) {
		$property = new SMWPropertyValue('__pro');
		$property->setUserValue($propertyname);
		return $property;
	}

	/**
	 * Static function for creating a new property object from a
	 * property identifier (string) as it might be used internally.
	 * This might be the DB-key version of some property title
	 * text or the id of a predefined property (such as '_TYPE').
	 * @note The resulting property object might be invalid if
	 * the provided name is not allowed. An object is returned
	 * in any case.
	 */
	static public function makeProperty($propertyid) {
		$property = new SMWPropertyValue('__pro');
		$property->setXSDValue($propertyid);
		return $property;
	}

	/**
	 * Extended parsing function to first check whether value refers to pre-defined
	 * property, resolve aliases, and set internal property id accordingly.
	 * @todo Accept/enforce property namespace.
	 */
	protected function parseUserValue($value) {
		global $smwgContLang;
		if ($this->m_caption === false) { // always use this as caption
			$this->m_caption = $value;
		}
		$value = smwfNormalTitleText(ltrim(rtrim($value,' ]'),' [')); //slightly normalise label
		$this->m_propertyid = $smwgContLang->findSpecialPropertyID($value);
		if ($this->m_propertyid !== false) {
			$value = $smwgContLang->findSpecialPropertyLabel($this->m_propertyid);
		}
		if ($value !== false) {
			$this->m_wikipage = SMWDataValueFactory::newTypeIDValue('_wpp');
			$this->m_wikipage->setUserValue($value, $this->m_caption);
			$this->addError($this->m_wikipage->getErrors());
		} else { // should rarely happen ($value is only changed if the input $value really was a label for a predefined prop)
			$this->m_wikipage = NULL;
		}
	}

	protected function parseXSDValue($value, $unit) { // (ignore "unit")
		$this->m_stubdata = array($value);
	}

	/**
	 * Extended parsing function to first check whether value is the id of a
	 * pre-defined property, to resolve property names and aliases, and to set
	 * internal property id accordingly.
	 */
	protected function unstub() {
		if (is_array($this->m_stubdata)) {
			global $smwgContLang;
			if ($this->m_stubdata[0]{0} == '_') { // internal id, use as is (and hope it is still known)
				$this->m_propertyid = $this->m_stubdata[0];
			} else { // possibly name of special property
				$this->m_propertyid = $smwgContLang->findSpecialPropertyID(str_replace('_',' ',$this->m_stubdata[0]));
			}
			$label = ($this->m_propertyid !== false)?$smwgContLang->findSpecialPropertyLabel($this->m_propertyid):$this->m_stubdata[0];
			if ($label != '') {
				$this->m_wikipage = SMWDataValueFactory::newTypeIDValue('_wpp');
				$this->m_wikipage->setValues(str_replace(' ', '_',$label),SMW_NS_PROPERTY);
				$this->m_caption = $label;
				$this->addError($this->m_wikipage->getErrors()); // NOTE: this unstubs the wikipage, should we rather ignore errors here to prevent this?
			} else { // predefined property without label
				$this->m_wikipage = NULL;
				$this->m_caption = $this->m_propertyid;
			}
			$this->m_stubdata = false;
		}
	}

	/**
	 * Return TRUE if this is a usual wiki property that is defined by a wiki page, as
	 * opposed to a property that is pre-defined in the wiki.
	 */
	public function isUserDefined() {
		$this->unstub();
		return ($this->m_propertyid == '');
	}

	/**
	 * Return TRUE if this is a property that can be displayed, and not a pre-defined
	 * property that is used only internally and does not even have a user-readable name.
	 * @note Every user defined property is necessarily visible.
	 */
	public function isVisible() {
		$this->unstub();
		return ($this->m_wikipage !== NULL);
	}

	/**
	 * Specifies whether values of this property should be shown in typical browsing
	 * interfaces. A property may wish to prevent this if either (1) its information is
	 * really dull, e.g. being a mere copy of information that is obvious from other
	 * things that are shown, or (2) the property is set in a hook after parsing, so that
	 * it is not reliably available when Factboxes are displayed. Properties that are
	 * internal so that they should never be observed by users, then it is better to just
	 * not associate any translated label with them, so they never appear anywhere.
	 */
	public function isShown() {
		$this->unstub();
		return (($this->m_propertyid == '') ||
		        (array_key_exists($this->m_propertyid, SMWPropertyvalue::$m_propertytypes) &&
		         SMWPropertyvalue::$m_propertytypes[$this->m_propertyid][1]) );
	}

	public function getShortWikiText($linked = NULL) {
		return $this->isVisible()?$this->highlightText($this->m_wikipage->getShortWikiText($linked)):'';
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->isVisible()?$this->highlightText($this->m_wikipage->getShortHTMLText($linker)):'';
	}

	public function getLongWikiText($linked = NULL) {
		return $this->isVisible()?$this->highlightText($this->m_wikipage->getLongWikiText($linked)):'';
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->isVisible()?$this->highlightText($this->m_wikipage->getLongHTMLText($linker)):'';
	}

	/**
	 * Return internal property id as the main way of storing property references.
	 */
	public function getXSDValue() {
		$this->unstub();
		return $this->isVisible()?$this->m_wikipage->getXSDValue():$this->m_propertyid;
	}

	public function getWikiValue() {
		return $this->isVisible()?$this->m_wikipage->getWikiValue():'';
	}

	/**
	 * If this property is associated with a wiki page, return an SMWWikiPageValue for
	 * that page. Otherwise return NULL.
	 */
	public function getWikiPageValue() {
		$this->unstub();
		return $this->m_wikipage;
	}

	/**
	 * If this property was not user defined, return the internal ID string referring to
	 * that property. Otherwise return FALSE;
	 */
	public function getPropertyID() {
		$this->unstub();
		return $this->m_propertyid;
	}

	/**
	 * Return an SMWTypesValue object representing the datatype of this property.
	 */
	public function getTypesValue() {
		global $smwgPDefaultType;
		if (!$this->isValid()) { // errors in property, return invalid types value with same errors
			$result = SMWDataValueFactory::newTypeIDValue('__typ');
			$result->setXSDValue('__err');
			$result->addError($this->getErrors());
		} elseif ($this->isUserDefined()) { // normal property
			$typearray = smwfGetStore()->getPropertyValues($this->getWikiPageValue(),SMWPropertyValue::makeProperty('_TYPE'));
			if (count($typearray)==1) { // unique type given
				$result = current($typearray);
			} elseif (count($typearray)==0) { // no type given
				$result = SMWDataValueFactory::newTypeIDValue('__typ');
				$result->setXSDValue($smwgPDefaultType);
			} else { // many types given, error
				wfLoadExtensionMessages('SemanticMediaWiki');
				$result = SMWDataValueFactory::newTypeIDValue('__typ');
				$result->setXSDValue('__err');
				$result->addError(wfMsgForContent('smw_manytypes'));
			}
		} else { // pre-defined property
			$result = SMWDataValueFactory::newTypeIDValue('__typ');
			if (array_key_exists($this->m_propertyid, SMWPropertyValue::$m_propertytypes)) {
				$result->setXSDValue(SMWPropertyValue::$m_propertytypes[$this->m_propertyid][0]);
			} else { // fixed default for special properties
				$result->setXSDValue('_str');
			}
		}
		return $result;
	}

	/**
	 * Quickly get the type id of some property without necessarily making another datavalue.
	 */
	public function getTypeID() {
		$type = $this->getTypesValue();
		return $type->isUnary()?$type->getXSDValue():'__nry';
	}

	/**
	 * Create special highlighting for hinting at special properties.
	 */
	protected function highlightText($text) {
		if ($this->isUserDefined()) {
			return $text;
		} else {
			SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
			return '<span class="smwttinline"><span class="smwbuiltin">' . $text .
			'</span><span class="smwttcontent">' . wfMsgForContent('smw_isspecprop') . '</span></span>';
		}
	}

}
