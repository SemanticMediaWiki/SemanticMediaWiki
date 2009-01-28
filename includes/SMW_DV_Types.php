<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining
 * types of properties (n-ary or binary).
 * Two main use-cases exist for this class:
 * - to parse and format a use-provided string in a rather tolerant way
 * - to efficiently be generated from DB keys and to provide according
 *   wiki values, in order to support speedy creation of datavalues in
 *   SMWDataValueFactory.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTypesValue extends SMWDataValue {

	private $m_typelabels = false;
	private $m_typecaptions = false;
	private $m_xsdvalue = false;
	private $m_isalias = false; // record whether this is an alias to another type, used to avoid duplicates when listing page types

	protected function parseUserValue($value) {
		// no use for being lazy here: plain user values are never useful
		$this->m_typelabels = array();
		$this->m_typecaptions = array();
		$types = explode(';', $value);
		foreach ($types as $type) {
			$type = ltrim($type, ' [');
			$type = rtrim($type, ' ]');
			$ttype = Title::newFromText($type,SMW_NS_TYPE);
			if ( ($ttype !== NULL) && ($ttype->getNamespace() == SMW_NS_TYPE) ) {
				$this->m_typecaptions[] = $type;
				$label = SMWDataValueFactory::findTypeLabel(SMWDataValueFactory::findTypeID($ttype->getText()));
				$this->m_typelabels[] = $label;
				$this->m_isalias = ($label === $ttype->getText())?false:true;
			} // else: wrong namespace or invalid title given -- what now? TODO
		}
	}

	protected function parseDBkeys($args) {
		$this->m_xsdvalue = $args[0]; // lazy parsing
		$this->m_isalias = false;
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		if ( ($linked === NULL) || ($linked === false) || ($this->m_caption === '') ) {
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
		$this->unstub();
		if ( ($linker === NULL) || ($linker === false) || ($this->m_caption === '') ) {
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
			$captions = $this->getTypeCaptions();
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
		$this->unstub();
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
				$id = SMWDataValueFactory::findTypeID($type);
				if ($id{0} == '_') { // builtin
					wfLoadExtensionMessages('SemanticMediaWiki');
					SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
					$result .= '<span class="smwttinline"><span class="smwbuiltin">[[' . $typenamespace . ':' . $type . '|' . $type . ']]</span><span class="smwttcontent">' . wfMsgForContent('smw_isknowntype') . '</span></span>';
				} else {
					$result .= '[[' . $typenamespace . ':' . $type . '|' . $type . ']]';
				}
			}
			return $result;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		$this->unstub();
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
				$id = SMWDataValueFactory::findTypeID($type);
				if ($id{0} == '_') { // builtin
					wfLoadExtensionMessages('SemanticMediaWiki');
					SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
					$result .= '<span class="smwttinline"><span class="smwbuiltin">' .
					$linker->makeLinkObj( $title, $type) . '</span><span class="smwttcontent">' .
					wfMsgForContent('smw_isknowntype') . '</span></span>';
				} else {
					$result .= $linker->makeLinkObj( $title, $type);
				}
			}
			return $result;
		}
	}

	public function getDBkeys() {
		return ($this->isValid())?array($this->getDBkey()):array(false);
	}

	public function getWikiValue() {
		return implode('; ', $this->getTypeLabels());
	}

	public function getHash() {
		return implode("\t", $this->getTypeLabels());
	}

	/**
	 * Convenience method to obtain the (single) DB key as a string (not in an array).
	 * Provided since many callers can use this hash for type recognition and registry.
	 *
	 */
	public function getDBkey() {
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
	}

	/**
	 * Is this a simple unary type or some composed n-ary type?
	 */
	public function isUnary() {
		$this->unstub();
		if ($this->m_typelabels !== false) {
			return (count($this->m_typelabels) == 1);
		} elseif ($this->m_xsdvalue !== false) {
			return (count(explode(';', $this->getDBkey(),2)) == 1);
		} else { //invalid
			return false;
		}
	}

	/**
	 * Is this a built-in datatype shipped with SMW (or an extension of SMW)?
	 * (Alternatively it would be a user-defined derived datatype.)
	 */
	public function isBuiltIn() {
		$v = $this->getDBkey();
		return ( ($this->isUnary()) && ($v{0} == '_') );
	}

	/**
	 * Is this an alias for another datatype in SMW? This information is used to
	 * explain entries in Special:Types that are found since they have pages.
	 */
	public function isAlias() {
		$this->unstub();
		return $this->m_isalias;
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
	 * Internal method to extract data from DB representation. Called lazily.
	 */
	protected function initTypeData() {
		$this->unstub();
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
	 * @bug This implementation is inefficient.
	 */
	public function getTypeValues() {
		$result = array();
		$i = 0;
		foreach ($this->getTypeLabels() as $tl) {
			$result[$i] = SMWDataValueFactory::newPropertyObjectValue(SMWPropertyValue::makeProperty('_TYPE'), $tl);
			$i++;
		}
		return $result;
	}

}

