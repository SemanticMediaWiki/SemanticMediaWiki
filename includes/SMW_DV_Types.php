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
	private $m_typecaptions = false;
	private $m_xsdvalue = false;

	protected function parseUserValue($value) {
		// no use for being lazy here: plain user values are never useful
		$this->m_typelabels = array();
		$types = explode(';', $value);
		foreach ($types as $type) {
			$type = ltrim($type, ' [');
			$type = rtrim($type, ' ]');
			$ttype = Title::newFromText($type,SMW_NS_TYPE);
			if ($ttype->getNamespace() == SMW_NS_TYPE) {
				$this->m_typecaptions[] = $type;
				$this->m_typelabels[] = SMWDataValueFactory::findTypeLabel(SMWDataValueFactory::findTypeID($ttype->getText()));
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
		if ( ($linked === NULL) || ($linked === false) ) {
			if ($this->m_caption !== false) {
				return $this->m_caption;
			} else {
				return str_replace('_',' ',implode(', ', $this->getTypeCaptions()));
			}
		} else {
			global $wgContLang;
			$typenamespace = $wgContLang->getNsText(SMW_NS_TYPE);
			if ($this->m_caption !== false) {
				if ($this->isUnary()) {
					return '[[' . $typenamespace . ':' . $this->getWikiValue() . '|' . $this->m_caption . ']]';
				} else {
					return $this->m_caption;
				}
			}
			$result = '';
			$first = true;
			$captions = $this->getTypeCaptions();
			reset($captions);
			foreach ($this->getTypeLabels() as $type) {
				$caption = current($captions);
				if ($first) {
					$first = false;
				} else {
					$result .= ', ';
				}
				$result .= '[[' . $typenamespace . ':' . $type . '|' . $caption . ']]';
				next($captions);
			}
			return $result;
		}
	}

	public function getShortHTMLText($linker = NULL) {
		if ( ($linker === NULL) || ($linker === false) ) {
			if ($this->m_caption !== false) {
				return htmlspecialchars($this->m_caption);
			} else {
				return str_replace('_',' ',implode(', ', $this->getTypeCaptions()));
			}
		} else {
			if ($this->m_caption !== false) {
				if ($this->isUnary()) {
					$title = Title::newFromText($this->getWikiValue(), SMW_NS_TYPE);
					return $linker->makeLinkObj($title, $this->m_caption);
				} else {
					return htmlspecialchars($this->m_caption);
				}
			}
			$result = '';
			$first = true;
			reset($captions);
			foreach ($this->getTypeLabels() as $type) {
				$caption = current($captions);
				if ($first) {
					$first = false;
				} else {
					$result .= ', ';
				}
				$title = Title::newFromText($type, SMW_NS_TYPE);
				$result .= $linker->makeLinkObj( $title, $caption);
				next($captions);
			}
			return $result;
		}
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
					$this->m_xsdvalue .= SMWDataValueFactory::findTypeID($label);
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
	 * Is this a built-in datatype shipped with SMW (or an extension of SMW)?
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
		$this->initTypeData();
		if ($this->m_typelabels === false) {
			return array(); // fallback for unary callers
		} else {
			return $this->m_typelabels;
		}
	}

	/**
	 * Retrieve type captions if needed. Can be done lazily. The captions 
	 * are different from the labels if type aliases are used.
	 */
	public function getTypeCaptions() {
		$this->initTypeData();
		if ($this->m_typecaptions === false) {
			return array(); // fallback for unary callers
		} else {
			return $this->m_typecaptions;
		}
	}

	/**
	 * Internal method to extract data from XSD-representation. Called lazily.
	 */
	protected function initTypeData() {
		if ( ($this->m_typelabels === false) && ($this->m_xsdvalue !== false) ) {
			$this->m_typelabels = array();
			$ids = explode(';', $this->m_xsdvalue);
			foreach ($ids as $id) {
				$label = SMWDataValueFactory::findTypeLabel($id);
				$this->m_typelabels[] = $label;
				$this->m_typecaptions[] = $label;
			}
		}
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

}

