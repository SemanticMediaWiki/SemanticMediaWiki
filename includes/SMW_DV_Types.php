<?php

/**
 * This datavalue implements special processing suitable for defining
 * types of properties (n-ary or binary).
 */
class SMWTypesValue extends SMWDataValue {

	private $m_typevalues = array();
	private $m_error = '';

	/*********************************************************************/
	/* Set methods                                                       */
	/*********************************************************************/

	public function setUserValue($value) {
		$this->m_typevalues = array();
		$types = explode(';', $value);
		foreach ($types as $type) {
			$type = ltrim($type, ' [');
			$type = rtrim($type, ' ]');
			$ttype = Title::newFromText($type,SMW_NS_TYPE);
			if ($ttype->getNamespace() == SMW_NS_TYPE) {
				$this->m_typevalues[] = $ttype->getText();
			} // else: wrong namespace given -- what now? TODO
		}
	}

	public function setXSDValue($value, $unit) {
		$this->setUserValue($value); // no units, compatible syntax
	}

	public function setAttribute($attribute) { // ignore
	}

	/*********************************************************************/
	/* Get methods                                                       */
	/*********************************************************************/

	public function getShortWikiText($linked = NULL) {
		if ( ($linked === NULL) || ($linked === false) ) {
			return implode(', ', $this->m_typevalues);
		} else {
			global $wgContLang;
			$result = '';
			$typenamespace = $wgContLang->getNsText(SMW_NS_TYPE);
			$first = true;
			foreach ($this->m_typevalues as $type) {
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

	public function getShortHTMLText($linker = NULL) {
		///TODO Support linking
		return implode(', ', $this->m_typevalues);
	}

	public function getLongWikiText($linked = NULL) {
		return $this->getShortWikiText($linked);
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getShortHTMLText($linker);
	}

	public function getXSDValue() {
		if ($this->isValid()) {
			return str_replace(' ','_',implode(', ', $this->m_typevalues));
		} else {
			return false;
		}
	}

	public function getNumericValue() {
		return NULL;
	}

	public function getUnit() {
		return false;
	}

	public function getError() {
		return $this->m_error;
	}

	public function getInfolinks() {
		return array();
	}

	public function getHash() {
		return implode('[]', $this->m_typevalues);
	}

	public function isValid() {
		return ( ($this->m_error == '') && (count($this->m_typevalues)>0) );
	}

	public function isNumeric() {
		return false;
	}


}

?>