<?php

global $smwgIP;
include_once($smwgIP . '/includes/SMW_DataValue.php');

/**
 * This datavalue implements special processing suitable for defining
 * types of properties (n-ary or binary).
 * Two main use-cases exist for this class:
 * - to parse and format a use-provided string in a rather tolerant way
 * - to efficiently be generated from XSD values and to provide according 
 *   wiki values, in order to support speedy creation of datavalues in
 *   SMWDataValueFactory.
 */
class SMWTypesValue extends SMWDataValue {

	private $m_typelabels = false;
	private $m_xsdvalue = false;

	/**
	 * This associative array links message ids for type label to internal type
	 * ids. Datavalue classes register for certain internal type ids, and this
	 * class establishes the required mapping. No id must be duplicated, and
	 * ids should in general not be changed, since they act as universal handles
	 * for types throughout the code. The registration happens in 
	 * SMW_DataValueFactory.php
	 */
	static private $m_typeids = array(
		'smw_wikipage'      => '_wpg',
		'smw_string'        => '_str',
		'smw_text'          => '_txt',
		'smw_enum'          => '_enu',
		'smw_bool'          => '_boo',
		'smw_int'           => '_int',
		'smw_float'         => '_flt',
		//case 'smw_length'
		//case 'smw_area'
		//case 'smw_geolength'
		//case 'smw_geoarea'
		'smw_geocoordinate' => '_crd',
		//case 'smw_mass'
		'smw_time'          => '_tim',
		'smw_temperature'   => '_tmp',
		'smw_datetime'      => '_dat',
		'smw_email'         => '_ema',
		'smw_url'           => '_url',
		'smw_uri'           => '_uri',
		'smw_annouri'       => '_anu'
	);

	protected function parseUserValue($value) {
		// no use for being lazy here: plain user values are never useful
		$this->m_typelabels = array();
		$types = explode(';', $value);
		foreach ($types as $type) {
			$type = ltrim($type, ' [');
			$type = rtrim($type, ' ]');
			$ttype = Title::newFromText($type,SMW_NS_TYPE);
			if ($ttype->getNamespace() == SMW_NS_TYPE) {
				$this->m_typelabels[] = $ttype->getText();
			} // else: wrong namespace given -- what now? TODO
		}
	}

	protected function parseXSDValue($value, $unit) {
		$this->m_xsdvalue = $value; // lazy parsing
	}

	public function setOutputFormat($formatstring) {
		// no output formats supported, ignore
	}

	public function getShortWikiText($linked = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		return $this->getLongWikiText($linked);
	}

	public function getShortHTMLText($linker = NULL) {
		if ($this->m_caption !== false) {
			return htmlspecialchars($this->m_caption);
		}
		return $this->getLongHTMLText($linker);
	}

	public function getLongWikiText($linked = NULL) {
		if ( ($linked === NULL) || ($linked === false) ) {
			return str_replace('_',' ',implode(', ', $this->getTypeLabels()));
		} else {
			global $wgContLang;
			$result = '';
			$typenamespace = $wgContLang->getNsText(SMW_NS_TYPE);
			$first = true;
			foreach ($this->getTypeLabels() as $type) {
				if ($first) {
					$first = false;
				} else {
					$result .= ', ';
				}
				$result .= '[[' . $typenamespace . ':' . $type . '|' . $type . ']]';
			}
			return $result;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if ( ($linker === NULL) || ($linker === false) ) {
			return str_replace('_',' ',implode(', ', $this->getTypeLabels()));
		} else {
			$result = '';
			$first = true;
			foreach ($this->getTypeLabels() as $type) {
				if ($first) {
					$first = false;
				} else {
					$result .= ', ';
				}
				$title = Title::newFromText($type, SMW_NS_TYPE);
				$result .= $linker->makeLinkObj( $title, $type);
			}
			return $result;
		}
	}

	public function getXSDValue() {
		if ($this->isValid()) {
			if ($this->m_xsdvalue === false) {
				$first = true;
				$this->m_xsdvalue = '';
				foreach ($this->m_typelabels as $label) {
					if ($first) {
						$first = false;
					} else {
						$this->m_xsdvalue .= ';';
					}
					$this->m_xsdvalue .= SMWTypesValue::findTypeID($label);
				}
			}
			return $this->m_xsdvalue;
		} else {
			return false;
		}
	}

	public function getWikiValue() {
		return implode('; ', $this->getTypeLabels());
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		return ''; // empty unit
	}

	public function getInfolinks() {
		return array();
	}

	public function getHash() {
		return implode('[]', $this->getTypeLabels());
	}

	public function isNumeric() {
		return false;
	}

	/**
	 * Is this a simple unary type or some composed n-ary type?
	 */
	public function isUnary() {
		if ($this->m_typelabels !== false) {
			return (count($this->m_typelabels) == 1);
		} elseif ($this->m_xsdvalue !== false) {
			return (count(explode(';', $this->getXSDValue(),2)) == 1);
		} else { //invalid
			return false;
		}
	}

	/**
	 * Is this a built-in datatype shipped with SMW?
	 * (Alternatively it would be a user-defined derived datatype.)
	 */
	public function isBuiltIn() {
		$v = $this->getXSDValue();
		return ( ($this->isUnary()) && ($v[0] == '_') );
	}

	/**
	 * Retrieve type labels if needed. Can be done lazily.
	 */
	public function getTypeLabels() {
		if ( ($this->m_typelabels === false) && ($this->m_xsdvalue !== false) ) {
			$this->m_typelabels = array();
			$ids = explode(';', $this->m_xsdvalue);
			foreach ($ids as $id) {
				$this->m_typelabels[] = SMWTypesValue::findTypeLabel($id);
			}
		} elseif ($this->m_typelabels === false) {
			return array(); // fallback for unwary callers
		}
		return $this->m_typelabels; // false only if nothing set yet
	}

	/**
	 * Retrieve type values.
	 * FIXME: wildly inefficient since new id management
	 */
	public function getTypeValues() {
		$result = array();
		$i = 0;
		foreach ($this->getTypeLabels() as $tl) {
			$result[$i] = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE, $tl);
			$i++;
		}
		return $result;
	}

	/**
	 * Get the language independent id for some type label (e.g. "_int" for "Integer").
	 * This id is used for all internal operations. Compound types are not supported
	 * by this method (decomposition happens earlier). Custom types get their DBkeyed 
	 * label as id. All ids are prefixed by an underscore in order to distinguish them 
	 * from custom types.
	 */
	static public function findTypeID($label) {
		global $smwgContLang;
		$msgid = $smwgContLang->findDatatypeMsgID($label);
		if ( ($msgid !== false) && (array_key_exists($msgid, SMWTypesValue::$m_typeids)) ) {
			return SMWTypesValue::$m_typeids[$msgid];
		} else { // hopefully $msgid was just FALSE ...
			return str_replace(' ', '_', $label);
		}
	}

	/**
	 * Inverse of findTypeID();
	 */
	static public function findTypeLabel($id) {
		global $smwgContLang;
		if ($id{0} === '_') {
			$key = array_search($id, SMWTypesValue::$m_typeids);
			if ($key !== false) {
				return $smwgContLang->getDatatypeLabel($key);
			} else { // maybe some no longer supported type?
				return str_replace('_', ' ', $id);
			}
		} else {
			return str_replace('_', ' ', $id);
		}
	}

}

