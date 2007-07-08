<?php

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
		/// TODO: support linking
		return implode(', ', $this->m_typelabels);
	}

	public function getXSDValue() {
		if ($this->isValid()) {
			if ($this->m_xsdvalue === false) {
				$this->m_xsdvalue = str_replace(' ','_',implode('; ', $this->m_typelabels));
			}
			return $this->m_xsdvalue;
		} else {
			return false;
		}
	}

	public function getWikiValue() {
		return str_replace( '_', ' ', $this->getXSDValue() );
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		return ''; // empty unit
	}

	public function getTypeID() {
		return 'types';
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
	 * Retrieve type labels if needed. Can be done lazily.
	 */
	public function getTypeLabels() {
		if ( ($this->m_typelabels === false) && ($this->m_xsdvalue !== false) ) {
			$this->m_typelabels = explode('; ', $this->m_xsdvalue);
		}
		return $this->m_typelabels; // false only if nothing set yet
	}

	/**
	 * Retrieve type values.
	 */
	public function getTypeValues() {
		$result = array();
		foreach ($this->getTypeLabels() as $tl) {
			$result[] = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE, $tl);
		}
		return $result;
	}

}

